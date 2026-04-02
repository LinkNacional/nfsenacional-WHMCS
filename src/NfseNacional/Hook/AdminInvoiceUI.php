<?php

namespace GK2\NfseNacional\Hook;

use GK2\NfseNacional\Domain\AmbienteGuard;
use GK2\NfseNacional\Persistence\NfseRepository;
use GK2\NfseNacional\Domain\Enum\NfseStatus;
use GK2\NfseNacional\Transport\ApiEndpoints;
use WHMCS\Database\Capsule;

/**
 * Renderiza o painel de NFS-e Nacional na pagina admin da fatura.
 *
 * Exibe status, botoes de acao (emitir, cancelar, reenviar email)
 * e links para DANFS-e e XML.
 *
 * Respeita controle de acesso por role de admin (campo "access").
 */
class AdminInvoiceUI
{
    /**
     * Renderiza o HTML do painel NFS-e Nacional.
     *
     * @param array $vars Variaveis do hook AdminInvoicesControlsOutput
     * @return string HTML do painel
     */
    public function render(array $vars): string
    {
        $invoiceId = (int) ($vars['invoiceid'] ?? 0);
        if ($invoiceId <= 0) {
            return '';
        }

        $guard = AmbienteGuard::getInstance();
        $repository = new NfseRepository($guard);
        $nfse = $repository->findByInvoice($invoiceId);

        $token = hash_hmac('sha1', (string) $invoiceId, 'nfsenacional');

        // Verificar permissao de acesso por role
        $adminId = $vars['adminid'] ?? \WHMCS\Session::get('adminid');
        $roleId = \WHMCS\User\Admin::findOrNew($adminId ?: 0)->roleId;
        $hasAccess = $this->checkAccess($roleId);

        // URL base do addon — relativa, pois o hook ja roda dentro do admin
        $baseUrl = 'addonmodules.php?module=nfsenacional';

        $html = '<div class="invoice-nfse-nacional" style="margin-top:10px;">';
        $html .= '<div class="panel panel-default">';
        $html .= '<div class="panel-heading"><h3 class="panel-title"><i class="fas fa-file-invoice"></i> NFS-e Nacional</h3></div>';
        $html .= '<div class="panel-body">';

        if ($nfse === null) {
            // Sem NFS-e - mostrar botao de emissao (se tem acesso)
            $html .= '<p class="text-muted">Nenhuma NFS-e Nacional emitida para esta fatura.</p>';
            if ($hasAccess) {
                $html .= '<a href="' . $baseUrl . '&action=emitir&invoiceid=' . $invoiceId . '&token=' . $token . '" class="btn btn-success btn-sm" onclick="return confirm(\'Deseja Emitir NFS-e para fatura #' . $invoiceId . '?\');">';
                $html .= '<i class="fas fa-paper-plane"></i> Emitir NFS-e Nacional</a>';
            }
        } else {
            // Auto-refresh quando PROCESSANDO (Gap 6)
            if ($nfse->status === NfseStatus::PROCESSANDO) {
                $html .= '<meta http-equiv="refresh" content="5">';
            }

            // Status badge
            $html .= '<p><strong>Status:</strong> <span class="label ' . $nfse->status->badgeClass() . '">' . $nfse->status->label() . '</span></p>';

            // Dados da nota
            if ($nfse->numeroNfseNacional) {
                $html .= '<p><strong>Numero NFS-e:</strong> ' . htmlspecialchars($nfse->numeroNfseNacional) . '</p>';
            }
            if ($nfse->chaveAcesso) {
                $html .= '<p><strong>Chave de Acesso:</strong> <small>' . htmlspecialchars($nfse->chaveAcesso) . '</small></p>';
            }
            if ($nfse->dataAutorizacao) {
                $html .= '<p><strong>Autorizacao:</strong> ' . htmlspecialchars($nfse->dataAutorizacao) . '</p>';
            }
            if ($nfse->erro) {
                $html .= '<p class="text-danger"><strong>Erro:</strong> ' . htmlspecialchars($nfse->erro) . '</p>';
            }

            // Botoes de acao
            $html .= '<div style="margin-top:10px;">';

            // Ver DANFS-e — URL montada dinamicamente a partir da chave de acesso
            if ($nfse->chaveAcesso) {
                $endpoints = new ApiEndpoints();
                $guard = AmbienteGuard::getInstance();
                $danfseUrl = $endpoints->obterDanfse($guard->getAmbiente(), $nfse->chaveAcesso);
                $xmlUrl = $nfse->xmlUrl ?: $endpoints->consultarNfseSefin($guard->getAmbiente(), $nfse->chaveAcesso);

                $html .= '<a href="' . htmlspecialchars($danfseUrl) . '" target="_blank" class="btn btn-info btn-sm">';
                $html .= '<i class="fas fa-file-pdf"></i> DANFS-e</a> ';

                $html .= '<a href="' . htmlspecialchars($xmlUrl) . '" target="_blank" class="btn btn-default btn-sm">';
                $html .= '<i class="fas fa-code"></i> XML</a> ';
            } elseif ($nfse->danfseUrl) {
                $html .= '<a href="' . htmlspecialchars($nfse->danfseUrl) . '" target="_blank" class="btn btn-info btn-sm">';
                $html .= '<i class="fas fa-file-pdf"></i> DANFS-e</a> ';
            }

            // Reenviar email (todos podem)
            if ($nfse->isAutorizada()) {
                $html .= '<a href="' . $baseUrl . '&action=reenviar_email&invoiceid=' . $invoiceId . '&token=' . $token . '" class="btn btn-primary btn-sm">';
                $html .= '<i class="fas fa-envelope"></i> Reenviar Email</a> ';
            }

            // Cancelar (somente com acesso)
            if ($hasAccess && $nfse->podeCancelar()) {
                $cancelUrl = $baseUrl . '&action=cancelar&invoiceid=' . $invoiceId . '&token=' . $token;
                $html .= '<a href="javascript:void(0)" class="btn btn-danger btn-sm" onclick="if(confirm(\'Confirma o cancelamento da NFS-e #' . $invoiceId . '?\')){window.location.href=\'' . $cancelUrl . '\';}">';
                $html .= '<i class="fas fa-times"></i> Cancelar NFS-e</a> ';
            }

            // Re-emitir se erro (somente com acesso)
            if ($hasAccess && $nfse->status === NfseStatus::ERRO) {
                $html .= '<a href="' . $baseUrl . '&action=emitir&invoiceid=' . $invoiceId . '&token=' . $token . '" class="btn btn-warning btn-sm">';
                $html .= '<i class="fas fa-redo"></i> Reemitir</a> ';
            }

            $html .= '</div>';
        }

        $html .= '</div></div></div>';

        return $html;
    }

    /**
     * Verifica se o admin com o roleId informado tem acesso.
     *
     * Se o campo "access" estiver vazio, todos tem acesso.
     * Caso contrario, o roleId deve estar na lista.
     */
    private function checkAccess($roleId): bool
    {
        $access = Capsule::table('tbladdonmodules')
            ->where('module', 'nfsenacional')
            ->where('setting', 'access')
            ->value('value');

        if (empty($access)) {
            return true;
        }

        $allowed = array_map('trim', explode(',', (string) $access));
        return in_array((string) $roleId, $allowed, true);
    }
}
