<?php

namespace GK2\NfseNacional\Fiscal\Mapper;

use GK2\NfseNacional\Config\ModuleConfig;
use WHMCS\Database\Capsule;

/**
 * Mapeia dados do tomador (cliente) do WHMCS para o formato
 * esperado pelo DpsPayloadBuilder.
 *
 * O builder gera os elementos XML <toma> conforme XSD v1.01:
 * - CPF ou CNPJ (choice, baseado no tamanho do documento)
 * - xNome (razao social ou nome completo)
 * - end > endNac (cMun, CEP) + xLgr + nro + xCpl + xBairro
 * - fone
 * - email
 */
class TomadorMapper
{
    private ModuleConfig $config;

    public function __construct(?ModuleConfig $config = null)
    {
        $this->config = $config ?? new ModuleConfig();
    }

    /**
     * Mapeia os dados do tomador a partir dos dados do cliente WHMCS.
     *
     * @param array $invoice Dados da fatura (retorno de GetInvoice)
     * @return array Dados do tomador
     */
    public function map(array $invoice): array
    {
        $userId = $invoice['userid'];
        $client = $this->getClientData($userId);

        $documento = $this->getDocumento($userId, $client);
        $documento = preg_replace('/\D/', '', $documento);

        $tomador = [
            'documento' => $documento,
            'razaoSocial' => $this->getRazaoSocial($client),
            'email' => $client['email'] ?? '',
        ];

        // Endereco com campos XSD
        $endereco = $this->mapEndereco($client);
        if (!empty($endereco)) {
            $tomador['endereco'] = $endereco;
        }

        // Telefone
        $telefone = preg_replace('/\D/', '', $client['phonenumber'] ?? '');
        if (!empty($telefone)) {
            $tomador['telefone'] = $telefone;
        }

        return $tomador;
    }

    /**
     * Obtem dados completos do cliente via Local API.
     */
    private function getClientData(int $userId): array
    {
        $result = localAPI('GetClientsDetails', ['clientid' => $userId, 'stats' => false]);

        if ($result['result'] === 'success') {
            return $result;
        }

        return [];
    }

    /**
     * Obtem o documento (CPF/CNPJ) do cliente.
     */
    private function getDocumento(int $userId, array $client): string
    {
        $fonte = $this->config->getDocumentoCliente();

        if ($fonte === 'taxid') {
            return $client['tax_id'] ?? $client['taxid'] ?? '';
        }

        // Extrair ID do custom field do formato "[ID] Nome"
        if (preg_match('/^\[(\d+)\]/', $fonte, $matches)) {
            $fieldId = (int) $matches[1];

            return (string) Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $fieldId)
                ->where('relid', $userId)
                ->value('value');
        }

        return $client['tax_id'] ?? '';
    }

    /**
     * Determina a razao social / nome do tomador.
     */
    private function getRazaoSocial(array $client): string
    {
        $companyName = trim($client['companyname'] ?? '');

        if (!empty($companyName)) {
            return $this->sanitizeText($companyName);
        }

        $fullName = trim(($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? ''));
        return $this->sanitizeText($fullName);
    }

    /**
     * Mapeia o endereco do cliente para campos XSD.
     *
     * TCEndereco: endNac(cMun,CEP) + xLgr + nro + xCpl + xBairro
     */
    private function mapEndereco(array $client): array
    {
        $endereco = [];

        // xLgr — logradouro (address1)
        $logradouro = trim($client['address1'] ?? '');
        if (!empty($logradouro)) {
            $endereco['logradouro'] = $this->sanitizeText($logradouro);
        }

        // Tentar extrair numero do logradouro
        // Formato comum: "Rua Nome, 123" ou "Rua Nome 123"
        $numero = 'S/N';
        if (preg_match('/[,\s]+(\d+)\s*$/', $logradouro, $m)) {
            $numero = $m[1];
            // Remover o numero do logradouro
            $endereco['logradouro'] = $this->sanitizeText(preg_replace('/[,\s]+\d+\s*$/', '', $logradouro));
        }
        $endereco['numero'] = $numero;

        // xCpl — complemento (address2 pode ser complemento ou bairro)
        $address2 = trim($client['address2'] ?? '');

        // xBairro — bairro
        // No WHMCS nao existe campo bairro separado.
        // address2 geralmente é o bairro no Brasil.
        if (!empty($address2)) {
            $endereco['bairro'] = $this->sanitizeText($address2);
        }

        // endNac > cMun — codigo IBGE do municipio do tomador
        // Tentar buscar pelo nome da cidade + UF
        $cidade = trim($client['city'] ?? '');
        $uf = trim($client['state'] ?? '');
        $codigoMun = $this->getCodigoMunicipioIBGE($cidade, $uf);
        if (!empty($codigoMun)) {
            $endereco['cMun'] = $codigoMun;
        }

        // endNac > CEP
        $cep = preg_replace('/\D/', '', $client['postcode'] ?? '');
        if (!empty($cep)) {
            $endereco['cep'] = $cep;
        }

        return $endereco;
    }

    /**
     * Busca codigo IBGE do municipio.
     * Primeiro tenta pela tabela mod_nfsenacional_municipios (se existir),
     * senao usa o municipio do prestador como fallback.
     */
    private function getCodigoMunicipioIBGE(string $cidade, string $uf): string
    {
        // Fallback: usar o municipio do prestador
        // Em uma implementacao futura, pode-se consultar uma tabela IBGE
        return $this->config->getCodigoMunicipioPrestador();
    }

    /**
     * Remove caracteres de controle para campos fiscais.
     */
    private function sanitizeText(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
        return trim($text);
    }
}
