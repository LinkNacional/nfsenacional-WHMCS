<?php

namespace GK2\NfseNacional\Admin\Action;

use GK2\NfseNacional\Domain\AmbienteGuard;
use GK2\NfseNacional\Domain\Service\EmissaoService;

/**
 * Acao de emissao manual de NFS-e Nacional a partir do painel admin.
 */
class EmitirAction
{
    public function execute(string $modulelink): void
    {
        $invoiceId = (int) ($_REQUEST['invoiceid'] ?? 0);
        $token = $_REQUEST['token'] ?? '';

        // Validar token
        $expectedToken = hash_hmac('sha1', (string) $invoiceId, 'nfsenacional');
        if ($token !== $expectedToken || $invoiceId <= 0) {
            logActivity('NFS-e Nacional: Tentativa de emissao com token invalido. Invoice: ' . $invoiceId);
            header('Location: invoices.php?action=edit&id=' . $invoiceId . '&nfse_error=token');
            exit;
        }

        $guard = AmbienteGuard::getInstance();
        $service = new EmissaoService(null, $guard);
        $result = $service->processarEmissao($invoiceId, 'manual');

        $status = $result['sucesso'] ? 'success' : 'error';
        $msg = urlencode($result['msg'] ?? '');

        header('Location: invoices.php?action=edit&id=' . $invoiceId . '&nfse_status=' . $status . '&nfse_msg=' . $msg);
        exit;
    }
}
