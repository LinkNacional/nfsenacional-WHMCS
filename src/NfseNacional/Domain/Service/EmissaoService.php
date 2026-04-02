<?php

namespace GK2\NfseNacional\Domain\Service;

use GK2\NfseNacional\Config\ModuleConfig;
use GK2\NfseNacional\Domain\AmbienteGuard;
use GK2\NfseNacional\Domain\Enum\EmissaoPolitica;
use GK2\NfseNacional\Domain\Enum\NfseStatus;
use GK2\NfseNacional\Fiscal\NacionalProvider;
use GK2\NfseNacional\Fiscal\Payload\DpsPayloadBuilder;
use GK2\NfseNacional\Persistence\NfseRepository;
use GK2\NfseNacional\Persistence\DpsSequence;
use GK2\NfseNacional\Transport\ApiEndpoints;

/**
 * Orquestra o fluxo completo de emissao de NFS-e Nacional.
 *
 * O ambiente e resolvido via AmbienteGuard e propagado para
 * todas as dependencias. Nunca mistura producao com homologacao.
 */
class EmissaoService
{
    private ModuleConfig $config;
    private AmbienteGuard $guard;
    private NfseRepository $repository;
    private NacionalProvider $provider;
    private DpsPayloadBuilder $payloadBuilder;
    private DpsSequence $sequence;
    private EmailService $emailService;

    public function __construct(
        ?ModuleConfig $config = null,
        ?AmbienteGuard $guard = null,
        ?NfseRepository $repository = null,
        ?NacionalProvider $provider = null,
        ?DpsPayloadBuilder $payloadBuilder = null,
        ?DpsSequence $sequence = null,
        ?EmailService $emailService = null,
    ) {
        $this->config = $config ?? new ModuleConfig();
        $this->guard = $guard ?? AmbienteGuard::getInstance($this->config);
        $this->repository = $repository ?? new NfseRepository($this->guard);
        $this->provider = $provider ?? new NacionalProvider(null, $this->config, null, $this->guard);
        $this->payloadBuilder = $payloadBuilder ?? new DpsPayloadBuilder();
        $this->sequence = $sequence ?? new DpsSequence();
        $this->emailService = $emailService ?? new EmailService();
    }

    /**
     * Processa a emissao de NFS-e para uma fatura.
     *
     * @param int $invoiceId ID da fatura no WHMCS
     * @param string $origem Origem da chamada (hook, manual, cron)
     * @return array ['sucesso' => bool, 'msg' => string, 'data' => array]
     */
    public function processarEmissao(int $invoiceId, string $origem = 'hook'): array
    {
        $ambiente = $this->guard->getAmbiente();

        // 1. Verificar idempotencia (busca filtrada pelo ambiente ativo)
        $existente = $this->repository->findByInvoice($invoiceId);

        if ($existente !== null) {
            // Cross-check: a nota encontrada DEVE ser do ambiente ativo
            $existente->assertAmbiente($this->guard);

            if ($existente->isAutorizada()) {
                return [
                    'sucesso' => false,
                    'msg' => 'NFS-e ja autorizada para esta fatura.',
                    'data' => $existente->toArray(),
                ];
            }

            if ($existente->isProcessando()) {
                $updatedAt = strtotime($existente->updatedAt ?? '');
                if ($updatedAt && (time() - $updatedAt) < 60) {
                    return [
                        'sucesso' => false,
                        'msg' => 'NFS-e em processamento. Aguarde.',
                        'data' => $existente->toArray(),
                    ];
                }
            }
        }

        // 2. Obter dados da fatura via WHMCS Local API
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
        if ($invoice['result'] !== 'success') {
            return ['sucesso' => false, 'msg' => 'Fatura nao encontrada.', 'data' => []];
        }

        // 3. Verificar valor > 0
        if ((float) $invoice['total'] <= 0) {
            return ['sucesso' => false, 'msg' => 'Fatura com valor zero.', 'data' => []];
        }

        // 4. Obter proximo numero DPS (isolado por serie+ambiente)
        $serieDps = $this->config->getSerieDps();
        $numeroDps = $this->sequence->next($serieDps, $ambiente);

        try {
            // 5. Montar XML da DPS conforme XSD v1.01
            $dpsXml = $this->payloadBuilder->build($invoice, $numeroDps, $serieDps, $origem);

            // 6. Registrar como PROCESSANDO — grava o ambiente no registro
            $this->repository->createOrUpdate($invoiceId, [
                'id_client' => $invoice['userid'],
                'client_name' => $this->resolveClientName((int) $invoice['userid']),
                'total' => $invoice['total'],
                'status' => NfseStatus::PROCESSANDO->value,
                'numero_dps' => $numeroDps,
                'serie_dps' => $serieDps,
                'ambiente' => $ambiente->value,
                'data_emissao' => date('Y-m-d H:i:s'),
            ]);

            // 7. Enviar XML para API Nacional (provider compacta GZip+base64)
            $response = $this->provider->emitirDps($dpsXml);

            if ($response->success) {
                $updateData = [
                    'status' => NfseStatus::AUTORIZADA->value,
                    'data_autorizacao' => date('Y-m-d H:i:s'),
                    'erro' => null,
                ];

                $chaveAcesso = $response->data['chaveAcesso'] ?? null;

                if (!empty($chaveAcesso)) {
                    $updateData['chave_acesso'] = $chaveAcesso;

                    // URLs derivadas da chave de acesso
                    $endpoints = new ApiEndpoints();
                    $updateData['danfse_url'] = $endpoints->obterDanfse($ambiente, $chaveAcesso);
                    $updateData['xml_url']    = $endpoints->consultarNfseSefin($ambiente, $chaveAcesso);
                }
                if (!empty($response->data['idDps'])) {
                    $updateData['protocolo'] = $response->data['idDps'];
                }
                if (!empty($response->data['nfseXmlGZipB64'])) {
                    $updateData['xml_retorno'] = $response->data['nfseXmlGZipB64'];

                    // Extrair número da NFS-e do XML autorizado
                    $xmlNfse = @gzdecode(base64_decode($response->data['nfseXmlGZipB64']));
                    if ($xmlNfse && preg_match('/<nNFSe>(\w+)<\/nNFSe>/', $xmlNfse, $m)) {
                        $updateData['numero_nfse_nacional'] = $m[1];
                    }
                }

                $this->repository->createOrUpdate($invoiceId, $updateData);

                // Enviar email se configurado
                if ($this->config->isEmailHabilitado()) {
                    $this->emailService->enviar($invoiceId);
                }

                logModuleCall('nfsenacional', '[' . strtoupper($ambiente->value) . '] Emissao-Sucesso', [
                    'invoiceId' => $invoiceId,
                    'origem' => $origem,
                    'xml_length' => strlen($dpsXml),
                ], $response->data);

                return [
                    'sucesso' => true,
                    'msg' => 'NFS-e emitida com sucesso.',
                    'data' => $response->data,
                ];
            }

            // Erro na emissao
            $erroMsg = implode('; ', $response->errors);
            $this->repository->createOrUpdate($invoiceId, [
                'status' => NfseStatus::ERRO->value,
                'erro' => $erroMsg,
            ]);

            logModuleCall('nfsenacional', '[' . strtoupper($ambiente->value) . '] Emissao-Erro', [
                'invoiceId' => $invoiceId,
                'origem' => $origem,
            ], $response->rawBody);

            return ['sucesso' => false, 'msg' => 'Erro na emissao: ' . $erroMsg, 'data' => $response->data];
        } catch (\Exception $e) {
            $this->repository->createOrUpdate($invoiceId, [
                'status' => NfseStatus::ERRO->value,
                'erro' => $e->getMessage(),
            ]);

            logModuleCall('nfsenacional', '[' . strtoupper($ambiente->value) . '] Emissao-Exception', [
                'invoiceId' => $invoiceId,
                'origem' => $origem,
            ], $e->getMessage(), $e->getTraceAsString());

            return ['sucesso' => false, 'msg' => 'Excecao: ' . $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Verifica se deve emitir NFS-e para um cliente/fatura.
     */
    public function deveEmitir(int $userId, string $invoiceStatus): bool
    {
        $politica = $this->getPoliticaCliente($userId);

        if ($politica === null) {
            $politica = $this->config->getEmissaoPadrao();
        }

        return $politica->deveEmitir($invoiceStatus);
    }

    /**
     * Resolve o nome do cliente via GetClientsDetails.
     * Usa razão social quando disponível, senão nome completo.
     */
    private function resolveClientName(int $userId): string
    {
        $result = localAPI('GetClientsDetails', ['clientid' => $userId, 'stats' => false]);

        if (($result['result'] ?? '') !== 'success') {
            return '';
        }

        $company = trim($result['companyname'] ?? '');
        if (!empty($company)) {
            return $company;
        }

        return trim(($result['firstname'] ?? '') . ' ' . ($result['lastname'] ?? ''));
    }

    private function getPoliticaCliente(int $userId): ?EmissaoPolitica
    {
        $fieldId = \WHMCS\Database\Capsule::table('tblcustomfields')
            ->where('fieldname', 'Emitir NFS-e (Nacional)')
            ->value('id');

        if (empty($fieldId)) {
            return null;
        }

        $valor = \WHMCS\Database\Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', $fieldId)
            ->where('relid', $userId)
            ->value('value');

        if (empty($valor)) {
            return null;
        }

        $numero = (int) preg_replace('/\D.*/', '', $valor);
        return EmissaoPolitica::tryFrom($numero);
    }
}
