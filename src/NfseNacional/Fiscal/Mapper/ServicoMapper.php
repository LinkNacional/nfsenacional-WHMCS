<?php

namespace GK2\NfseNacional\Fiscal\Mapper;

use GK2\NfseNacional\Config\ModuleConfig;

/**
 * Mapeia itens da fatura WHMCS para a estrutura de servico
 * exigida pela API NFS-e Nacional.
 *
 * Trata:
 * - Composicao da descricao fiscal
 * - Codigo nacional do servico (NBS)
 * - Codigo municipal do servico
 * - CNAE
 * - Exclusao de Late Fees (se configurado)
 * - Personalizacao por grupo de produto (se habilitado)
 */
class ServicoMapper
{
    private ModuleConfig $config;

    public function __construct(?ModuleConfig $config = null)
    {
        $this->config = $config ?? new ModuleConfig();
    }

    /**
     * Tipos de item sempre excluídos da NFS-e (independente de configuração).
     */
    private const TIPOS_SEMPRE_EXCLUIDOS = [
        'PaymentGateway', // tarifas de boleto, cartão, etc.
        'Credit',         // créditos negativos de item
        'Tax',            // impostos sobre a fatura
    ];

    /**
     * Mapeia os itens da fatura para a estrutura de servico.
     *
     * @param array $invoice Dados da fatura (retorno de GetInvoice)
     * @return array Dados do servico no formato da API Nacional
     */
    public function map(array $invoice): array
    {
        $items           = $invoice['items']['item'] ?? [];
        $excluirLateFee  = $this->config->isExcluirLateFee();
        $descricaoPartes = [];
        $valorTotal      = 0.0;

        foreach ($items as $item) {
            $tipo = $item['type'] ?? '';

            if (in_array($tipo, self::TIPOS_SEMPRE_EXCLUIDOS, true)) {
                continue;
            }

            // Late Fee: excluir somente se configurado
            if ($tipo === 'LateFee' && $excluirLateFee) {
                continue;
            }

            $valor = (float) ($item['amount'] ?? 0);

            if ($valor <= 0) {
                continue;
            }

            $valorTotal += $valor;
            $descricao = trim($item['description'] ?? '');

            if (!empty($descricao)) {
                $descricaoPartes[] = $descricao;
            }
        }

        // Subtrair desconto da fatura se configurado
        if ($this->config->isDescontarDesconto()) {
            $desconto = (float) ($invoice['discount'] ?? 0);
            if ($desconto > 0) {
                $valorTotal -= $desconto;
            }
        }

        // Subtrair crédito de conta aplicado se configurado
        if ($this->config->isDescontarCredito()) {
            $credito = (float) ($invoice['credit'] ?? 0);
            if ($credito > 0) {
                $valorTotal -= $credito;
            }
        }

        $valorTotal = max(0.0, $valorTotal);

        // Obter codigos fiscais globais
        $codigosFiscais = $this->getCodigosFiscais($items);

        $discriminacao = implode("\n", $descricaoPartes);
        if (empty($discriminacao)) {
            $discriminacao = 'Servicos de tecnologia - Fatura #' . ($invoice['invoiceid'] ?? '');
        }

        return [
            'codigoServicoNacional' => $codigosFiscais['codigo_servico_nacional'],
            'codigoServico' => $codigosFiscais['codigo_servico'],
            'codigoMunicipal' => $codigosFiscais['codigo_municipal'],
            'cnae' => $codigosFiscais['cnae'],
            'discriminacao' => $this->sanitizeDiscriminacao($discriminacao),
            'valorServicos' => round($valorTotal, 2),
            'codigoMunicipioIncidencia' => $this->config->getCodigoMunicipioPrestador(),
        ];
    }

    /**
     * Retorna os codigos fiscais globais configurados no addon.
     */
    private function getCodigosFiscais(array $items): array
    {
        return [
            'codigo_servico_nacional' => $this->config->getCodigoServicoNacional(),
            'codigo_servico' => $this->config->getCodigoServico(),
            'codigo_municipal' => $this->config->getCodigoMunicipal(),
            'cnae' => $this->config->getCnae(),
        ];
    }

    /**
     * Sanitiza a discriminacao do servico para o campo fiscal.
     */
    private function sanitizeDiscriminacao(string $text): string
    {
        // Remover tags HTML
        $text = strip_tags($text);

        // Substituir quebras de linha por separador visual (APIs fiscais rejeitam \n em xDescServ)
        $text = str_replace(["\r\n", "\r", "\n"], ' | ', $text);

        // Remover caracteres de controle
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);

        // Limitar tamanho (2000 caracteres é um limite comum)
        if (mb_strlen($text) > 2000) {
            $text = mb_substr($text, 0, 1997) . '...';
        }

        return trim($text);
    }
}
