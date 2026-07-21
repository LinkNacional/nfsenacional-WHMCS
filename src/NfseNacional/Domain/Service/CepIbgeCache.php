<?php

namespace GK2\NfseNacional\Domain\Service;

/**
 * Lookup manual de CEP → código IBGE do município.
 *
 * Quando o ViaCEP estiver indisponível, consulta um arquivo JSON local
 * que o operador mantém manualmente.
 *
 * Arquivo: modules/addons/nfsenacional/data/cep_ibge.json
 *
 * Formatos aceitos:
 *   - CEP exato (8 dígitos):  "87160000": "4114203"
 *   - Prefixo de CEP (5 díg): "87160":    "4114203"
 *     (cobre toda a faixa de CEPs daquela cidade/bairro)
 *
 * Exemplo de arquivo:
 * {
 *   "87160000": "4114203",
 *   "87160":    "4114203",
 *   "79080":    "5003207"
 * }
 *
 * Para descobrir o código IBGE de uma cidade:
 *   https://servicodados.ibge.gov.br/api/v1/localidades/municipios
 *   ou consulte diretamente: https://viacep.com.br/ws/{CEP}/json/
 */
class CepIbgeCache
{
    private string $filePath;
    private ?array $cache = null;

    public function __construct()
    {
        $this->filePath = dirname(__DIR__, 4) . '/data/cep_ibge.json';
    }

    /**
     * Busca o código IBGE para um CEP (8 dígitos, sem hífen).
     *
     * Tenta na ordem:
     *   1. CEP exato (8 dígitos)
     *   2. Prefixo de 5 dígitos (faixa de CEP da cidade)
     *
     * Retorna null se não encontrado.
     */
    public function get(string $cep): ?string
    {
        $data = $this->load();

        // Tentativa 1: CEP exato
        if (isset($data[$cep])) {
            return (string) $data[$cep];
        }

        // Tentativa 2: prefixo de 5 dígitos
        $prefix = substr($cep, 0, 5);
        if (isset($data[$prefix])) {
            return (string) $data[$prefix];
        }

        return null;
    }

    /**
     * Retorna o caminho do arquivo de configuração manual.
     * Útil para exibir no painel admin ou em mensagens de log.
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (!file_exists($this->filePath)) {
            $this->cache = [];
            return $this->cache;
        }

        $json = file_get_contents($this->filePath);
        if ($json === false) {
            $this->cache = [];
            return $this->cache;
        }

        $data = json_decode($json, true);
        $this->cache = is_array($data) ? $data : [];
        return $this->cache;
    }
}
