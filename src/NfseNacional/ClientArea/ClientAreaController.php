<?php

namespace GK2\NfseNacional\ClientArea;

use GK2\NfseNacional\Persistence\NfseRepository;
use GK2\NfseNacional\Domain\Service\EmailService;

/**
 * Controller da area do cliente para o modulo NFS-e Nacional.
 *
 * Renderiza a listagem de NFS-e do cliente logado e trata
 * acoes como reenvio de email.
 */
class ClientAreaController
{
    private NfseRepository $repository;

    public function __construct()
    {
        $this->repository = new NfseRepository();
    }

    /**
     * Renderiza a pagina da area do cliente.
     *
     * @param array $vars Variaveis do WHMCS (clientarea)
     * @return array Retorno padrao do WHMCS clientarea
     */
    public function render(array $vars): array
    {
        $clientId = (int) ($_SESSION['uid'] ?? 0);

        // Tratar acao de reenvio de email
        if (isset($_GET['action']) && $_GET['action'] === 'reenviar') {
            $this->handleReenviar($clientId);
        }

        // Buscar NFS-e do cliente
        $notas = $this->repository->findByClient($clientId);

        // Preparar dados para o template
        $notasData = [];
        foreach ($notas as $nfse) {
            $notasData[] = [
                'id' => $nfse->id,
                'invoice_id' => $nfse->invoiceId,
                'numero' => $nfse->numeroNfseNacional ?? '-',
                'status' => $nfse->status->label(),
                'status_class' => $nfse->status->badgeClass(),
                'total' => number_format($nfse->total ?? 0, 2, ',', '.'),
                'data_autorizacao' => $nfse->dataAutorizacao ?? '-',
                'danfse_url' => $nfse->danfseUrl ?? '',
                'xml_url' => $nfse->xmlUrl ?? '',
                'pode_reenviar' => $nfse->isAutorizada(),
                'created_at' => $nfse->createdAt ?? '',
            ];
        }

        return [
            'pagetitle' => $vars['_lang']['overview'] ?? 'NFS-e Nacional',
            'tagline' => 'Notas Fiscais Eletronicas',
            'breadcrumb' => [
                'clientarea.php' => 'Area do Cliente',
                'index.php?m=nfsenacional' => 'NFS-e Nacional',
            ],
            'templatefile' => 'templates/client/home',
            'vars' => [
                'notas' => $notasData,
                'total' => count($notasData),
                'modulelink' => $vars['modulelink'] ?? 'index.php?m=nfsenacional',
            ],
            'forcessl' => true,
            'requirelogin' => true,
        ];
    }

    /**
     * Trata reenvio de email solicitado pelo cliente.
     */
    private function handleReenviar(int $clientId): void
    {
        $invoiceId = (int) ($_GET['invoiceid'] ?? 0);
        $token = $_GET['token'] ?? '';

        // Validar token
        $expectedToken = hash_hmac('sha1', $invoiceId . ':' . $clientId, 'nfsenacional');
        if ($token !== $expectedToken || $invoiceId <= 0) {
            return;
        }

        // Verificar se a nota pertence ao cliente
        $nfse = $this->repository->findByInvoice($invoiceId);
        if ($nfse === null || $nfse->clientId !== $clientId) {
            return;
        }

        $emailService = new EmailService();
        $emailService->enviar($invoiceId);
    }
}
