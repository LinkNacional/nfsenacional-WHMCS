<?php

namespace GK2\NfseNacional\Admin\Action;

use GK2\NfseNacional\Domain\Service\EmailService;

/**
 * Acao de reenvio de email de NFS-e Nacional a partir do painel admin.
 */
class ReenviarEmailAction
{
    /**
     * Executa o reenvio de email.
     */
    public function execute(string $modulelink): void
    {
        $invoiceId = (int) ($_REQUEST['invoiceid'] ?? 0);
        $token = $_REQUEST['token'] ?? '';

        // Validar token
        $expectedToken = hash_hmac('sha1', (string) $invoiceId, 'nfsenacional');
        if ($token !== $expectedToken || $invoiceId <= 0) {
            logActivity('NFS-e Nacional: Tentativa de reenvio de email com token invalido. Invoice: ' . $invoiceId);
            header('Location: invoices.php?action=edit&id=' . $invoiceId . '&nfse_error=token');
            exit;
        }

        $service = new EmailService();
        $result = $service->enviar($invoiceId);

        $status = $result['sucesso'] ? 'success' : 'error';
        $msg = urlencode($result['msg'] ?? '');

        header('Location: invoices.php?action=edit&id=' . $invoiceId . '&nfse_status=' . $status . '&nfse_msg=' . $msg);
        exit;
    }
}
