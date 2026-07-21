<?php

namespace GK2\NfseNacional\Fiscal\Mapper;

use GK2\NfseNacional\Config\ModuleConfig;
use WHMCS\Database\Capsule;

/**
 * Calcula e mapeia tributos (ISS, CSLL, PIS, COFINS, INSS, IR)
 * para o formato exigido pela API NFS-e Nacional.
 *
 * Trata:
 * - Aliquota e valor do ISS
 * - Retencao de ISS (global ou por cliente)
 * - Exigibilidade do ISS
 * - Demais tributos federais (placeholders para implementacao futura)
 */
class TributoMapper
{
    private ModuleConfig $config;

    public function __construct(?ModuleConfig $config = null)
    {
        $this->config = $config ?? new ModuleConfig();
    }

    /**
     * Calcula os tributos para o valor informado.
     *
     * @param float $valorServicos Valor total dos servicos
     * @param int $clientId ID do cliente (para verificar retencao individual)
     * @return array Tributos no formato da API Nacional
     */
    public function map(float $valorServicos, int $clientId): array
    {
        $aliquotaIss = $this->config->getAliquotaIss();
        $issRetido = $this->isIssRetido($clientId);

        $valorIss = round($valorServicos * ($aliquotaIss / 100), 2);

        $tributos = [
            'issqn' => [
                'aliquota' => $aliquotaIss,
                'valor' => $valorIss,
                'retido' => $issRetido,
                'exigibilidade' => $this->getExigibilidadeIss(),
            ],
        ];

        // Valor de retencao do ISS
        if ($issRetido) {
            $retencaoPercent = $this->config->getRetencaoIss();
            $tributos['issqn']['valorRetido'] = round($valorServicos * ($retencaoPercent / 100), 2);
        }

        // Tributos federais (placeholders - implementar conforme necessidade)
        // Estes campos serao preenchidos quando houver regra definida
        $tributos['pis'] = ['aliquota' => 0, 'valor' => 0];
        $tributos['cofins'] = ['aliquota' => 0, 'valor' => 0];
        $tributos['inss'] = ['aliquota' => 0, 'valor' => 0];
        $tributos['ir'] = ['aliquota' => 0, 'valor' => 0];
        $tributos['csll'] = ['aliquota' => 0, 'valor' => 0];

        return $tributos;
    }

    /**
     * Verifica se o ISS deve ser retido para o cliente.
     */
    private function isIssRetido(int $clientId): bool
    {
        // Verificar custom field do cliente
        $fieldId = Capsule::table('tblcustomfields')
            ->where('fieldname', 'Reter ISS (Nacional)')
            ->value('id');

        if (!empty($fieldId)) {
            $valor = Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $fieldId)
                ->where('relid', $clientId)
                ->value('value');

            if ($valor === 'on') {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna o codigo de exigibilidade do ISS.
     *
     * 1 - Exigivel
     * 2 - Nao Incidencia
     * 3 - Isencao
     * 4 - Exportacao
     * 5 - Imunidade
     * 6 - Exigibilidade Suspensa por Decisao Judicial
     * 7 - Exigibilidade Suspensa por Processo Administrativo
     */
    private function getExigibilidadeIss(): int
    {
        return $this->config->getExigibilidadeIss();
    }
}
