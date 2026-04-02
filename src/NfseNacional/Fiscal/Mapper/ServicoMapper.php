<?php

namespace GK2\NfseNacional\Fiscal\Mapper;

use GK2\NfseNacional\Config\ModuleConfig;
use WHMCS\Database\Capsule;

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
     * Mapeia os itens da fatura para a estrutura de servico.
     *
     * @param array $invoice Dados da fatura (retorno de GetInvoice)
     * @return array Dados do servico no formato da API Nacional
     */
    public function map(array $invoice): array
    {
        $items = $invoice['items']['item'] ?? [];
        $descricaoPartes = [];
        $valorTotal = 0.0;

        foreach ($items as $item) {
            // Excluir Late Fee se configurado
            if ($this->config->isExcluirLateFee() && ($item['type'] ?? '') === 'LateFee') {
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

        // Considerar descontos se configurado
        if ($this->config->isConsiderarDescontos()) {
            $credit = (float) ($invoice['credit'] ?? 0);
            if ($credit > 0) {
                $valorTotal -= $credit;
            }
        }

        // Obter codigos fiscais (globais ou por grupo de produto)
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
     * Obtem codigos fiscais, considerando personalizacao por grupo.
     */
    private function getCodigosFiscais(array $items): array
    {
        // Se personalizacao por grupo esta habilitada, tentar buscar
        if ($this->config->isProdutosPersonalizados() && !empty($items)) {
            $grupoConfig = $this->getGrupoConfig($items);
            if ($grupoConfig !== null) {
                return $grupoConfig;
            }
        }

        // Codigos globais
        return [
            'codigo_servico_nacional' => $this->config->getCodigoServicoNacional(),
            'codigo_servico' => $this->config->getCodigoServico(),
            'codigo_municipal' => $this->config->getCodigoMunicipal(),
            'cnae' => $this->config->getCnae(),
        ];
    }

    /**
     * Busca configuracao fiscal especifica do grupo de produto.
     */
    private function getGrupoConfig(array $items): ?array
    {
        // Buscar primeiro item que tenha relid (referencia a um servico/produto)
        foreach ($items as $item) {
            $relId = $item['relid'] ?? 0;
            if (empty($relId)) {
                continue;
            }

            // Buscar grupo do produto
            $groupId = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
                ->where('tblhosting.id', $relId)
                ->value('tblproducts.gid');

            if (empty($groupId)) {
                continue;
            }

            // Buscar configuracao do grupo
            $grupo = Capsule::table('mod_nfsenacional_grupo')
                ->where('idgrupo', $groupId)
                ->first();

            if ($grupo) {
                return [
                    'codigo_servico_nacional' => $grupo->codigo_servico_nacional ?? $this->config->getCodigoServicoNacional(),
                    'codigo_servico' => $grupo->codigoatividade ?? $this->config->getCodigoServico(),
                    'codigo_municipal' => $grupo->codigomunicipal ?? $this->config->getCodigoMunicipal(),
                    'cnae' => $grupo->cnae ?? $this->config->getCnae(),
                ];
            }
        }

        return null;
    }

    /**
     * Sanitiza a discriminacao do servico para o campo fiscal.
     */
    private function sanitizeDiscriminacao(string $text): string
    {
        // Remover tags HTML
        $text = strip_tags($text);

        // Substituir quebras de linha por espaço (APIs fiscais rejeitam \n em xDescServ)
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);

        // Remover caracteres de controle
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);

        // Limitar tamanho (2000 caracteres é um limite comum)
        if (mb_strlen($text) > 2000) {
            $text = mb_substr($text, 0, 1997) . '...';
        }

        return trim($text);
    }
}
