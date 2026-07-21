<?php

namespace GK2\NfseNacional\Admin\Action;

use GK2\NfseNacional\Domain\Service\EmailService;
use GK2\NfseNacional\Security\TokenSigner;

/**
 * Acao de reenvio de email de NFS-e Nacional a partir do painel admin.
 *
 * Redireciona de volta para a origem:
 * - Se chamado da página de detalhe do módulo (from=detail): volta ao detalhe da NFS-e.
 * - Se chamado da página de edição da fatura (hook AdminInvoicesControlsOutput): volta à fatura.
 */
class ReenviarEmailAction
{
    /**
     * Executa o reenvio de email.
     */
    public function execute(string $modulelink): void
    {
        $invoiceId = (int) ($_REQUEST['invoiceid'] ?? 0);
        $token     = $_REQUEST['token'] ?? '';
        $from      = $_REQUEST['from'] ?? '';
        $nfseId    = (int) ($_REQUEST['nfseid'] ?? 0);

        // URL de retorno: módulo (detalhe) ou fatura
        if ($from === 'detail' && $nfseId > 0) {
            $returnBase = $modulelink . '&action=detail&id=' . $nfseId;
        } else {
            $returnBase = 'invoices.php?action=edit&id=' . $invoiceId;
        }

        if ($invoiceId <= 0 || !TokenSigner::verify((string) $invoiceId, $token)) {
            logActivity('NFS-e Nacional: Tentativa de reenvio de email com token invalido. Invoice: ' . $invoiceId);
            header('Location: ' . $returnBase . '&nfse_status=error&nfse_msg=' . urlencode('Token inválido.'));
            exit;
        }

        $service = new EmailService();
        $result  = $service->enviar($invoiceId);

        $status = $result['sucesso'] ? 'success' : 'error';
        $msg    = urlencode($result['msg'] ?? '');

        header('Location: ' . $returnBase . '&nfse_status=' . $status . '&nfse_msg=' . $msg);
        exit;
    }
}
