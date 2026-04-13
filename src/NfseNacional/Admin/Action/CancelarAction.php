<?php

namespace GK2\NfseNacional\Admin\Action;

use GK2\NfseNacional\Domain\AmbienteGuard;
use GK2\NfseNacional\Domain\Service\CancelamentoService;
use GK2\NfseNacional\Security\TokenSigner;

/**
 * Acao de cancelamento manual de NFS-e Nacional a partir do painel admin.
 */
class CancelarAction
{
    public function execute(string $modulelink): void
    {
        $invoiceId = (int) ($_REQUEST['invoiceid'] ?? 0);
        $token = $_REQUEST['token'] ?? '';

        // Validar token
        if ($invoiceId <= 0 || !TokenSigner::verify((string) $invoiceId, $token)) {
            logActivity('NFS-e Nacional: Tentativa de cancelamento com token invalido. Invoice: ' . $invoiceId);
            header('Location: invoices.php?action=edit&id=' . $invoiceId . '&nfse_error=token');
            exit;
        }

        $guard = AmbienteGuard::getInstance();
        $service = new CancelamentoService(null, $guard);
        $result = $service->cancelar($invoiceId);

        $status = $result['sucesso'] ? 'success' : 'error';
        $msg = urlencode($result['msg'] ?? '');

        header('Location: invoices.php?action=edit&id=' . $invoiceId . '&nfse_status=' . $status . '&nfse_msg=' . $msg);
        exit;
    }
}
