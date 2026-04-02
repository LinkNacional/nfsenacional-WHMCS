<?php

namespace GK2\NfseNacional\Admin;

use GK2\NfseNacional\Admin\Action\EmitirAction;
use GK2\NfseNacional\Admin\Action\CancelarAction;
use GK2\NfseNacional\Admin\Action\ExcluirAction;
use GK2\NfseNacional\Admin\Action\ReenviarEmailAction;
use GK2\NfseNacional\Domain\Entity\Nfse;
use GK2\NfseNacional\Domain\Enum\NfseStatus;
use GK2\NfseNacional\Persistence\NfseRepository;

/**
 * Controller principal da área administrativa do addon.
 *
 * Despacha ações (dashboard, list, detail, emitir, cancelar, reenviar)
 * e renderiza os templates Bootstrap correspondentes.
 */
class AdminController
{
    private NfseRepository $repository;

    // Mapa de status → cor da borda do card
    private const STATUS_COLORS = [
        'autorizadas'  => '#5cb85c',
        'processando'  => '#5bc0de',
        'erros'        => '#d9534f',
        'canceladas'   => '#f0ad4e',
    ];

    // Rótulos legíveis para os campos da entidade no detalhe
    private const FIELD_LABELS = [
        'id'                   => 'ID',
        'id_client'            => 'ID do Cliente',
        'id_invoice'           => 'Fatura',
        'client_name'          => 'Cliente',
        'total'                => 'Valor (R$)',
        'status'               => 'Status',
        'numero_dps'           => 'Número DPS',
        'serie_dps'            => 'Série DPS',
        'chave_acesso'         => 'Chave de Acesso',
        'numero_nfse_nacional'  => 'Número NFS-e',
        'protocolo'            => 'Protocolo',
        'codigo_verificacao'   => 'Código de Verificação',
        'danfse_url'           => 'Link DANFS-e',
        'xml_url'              => 'Link XML',
        'ambiente'             => 'Ambiente',
        'erro'                 => 'Mensagem de Erro',
        'data_emissao'         => 'Data de Emissão',
        'data_autorizacao'     => 'Data de Autorização',
        'created_at'           => 'Criado em',
        'updated_at'           => 'Atualizado em',
    ];

    public function __construct()
    {
        $this->repository = new NfseRepository();
    }

    // ── Dispatch ─────────────────────────────────────────────────────

    public function dispatch(array $vars): void
    {
        $action     = $_REQUEST['action'] ?? '';
        $modulelink = $vars['modulelink'] ?? '';

        switch ($action) {
            case 'emitir':
                (new EmitirAction())->execute($modulelink);
                return;
            case 'cancelar':
                (new CancelarAction())->execute($modulelink);
                return;
            case 'reenviar_email':
                (new ReenviarEmailAction())->execute($modulelink);
                return;
            case 'excluir':
                (new ExcluirAction())->execute($modulelink);
                return;
            case 'list':
                $this->renderList($vars);
                return;
            case 'detail':
                $this->renderDetail($vars);
                return;
            default:
                $this->renderDashboard($vars);
                return;
        }
    }

    // ── Dashboard ─────────────────────────────────────────────────────

    private function renderDashboard(array $vars): void
    {
        $modulelink = $vars['modulelink'];
        $stats      = $this->repository->stats();
        $recentes   = $this->repository->search([], 'id', 'desc', 10);

        $this->renderNav($modulelink, '');
        $this->renderStyles();

        // ── Cards de estatísticas ──
        $cards = [
            ['label' => 'Autorizadas',  'value' => $stats['autorizadas'],  'color' => '#5cb85c', 'icon' => 'fa-check-circle'],
            ['label' => 'Processando',  'value' => $stats['processando'],  'color' => '#5bc0de', 'icon' => 'fa-sync-alt'],
            ['label' => 'Erros',        'value' => $stats['erros'],        'color' => '#d9534f', 'icon' => 'fa-exclamation-circle'],
            ['label' => 'Canceladas',   'value' => $stats['canceladas'],   'color' => '#f0ad4e', 'icon' => 'fa-ban'],
        ];

        echo '<div class="row nfse-stat-row">';
        foreach ($cards as $card) {
            echo '<div class="col-sm-3">';
            echo '<div class="panel panel-default nfse-stat-card" style="border-top:3px solid ' . $card['color'] . ';">';
            echo '<div class="panel-body">';
            echo '<div class="nfse-stat-icon" style="color:' . $card['color'] . ';">'
                . '<i class="fas ' . $card['icon'] . '"></i></div>';
            echo '<div class="nfse-stat-value" style="color:' . $card['color'] . ';">' . $card['value'] . '</div>';
            echo '<div class="nfse-stat-label">' . $card['label'] . ' — ' . date('m/Y') . '</div>';
            echo '</div></div></div>';
        }
        echo '</div>';

        // ── Últimas NFS-e ──
        echo '<div class="panel panel-default" style="margin-top:10px;">';
        echo '<div class="panel-heading nfse-panel-heading">'
            . '<i class="fas fa-history"></i> Últimas NFS-e'
            . '<a href="' . $modulelink . '&action=list" class="btn btn-xs btn-default pull-right">'
            . '<i class="fas fa-list"></i> Ver todas</a>'
            . '</div>';

        if (!empty($recentes['data'])) {
            echo '<table class="table table-striped table-hover nfse-table" style="margin-bottom:0;">';
            echo '<thead><tr>'
                . '<th>ID</th><th>Fatura</th><th>Cliente</th>'
                . '<th>Valor</th><th>Status</th><th>Atualizado em</th>'
                . '<th></th>'
                . '</tr></thead><tbody>';

            foreach ($recentes['data'] as $nfse) {
                echo '<tr>';
                echo '<td><small class="text-muted">#' . $nfse->id . '</small></td>';
                echo '<td><a href="invoices.php?action=edit&id=' . $nfse->invoiceId . '">#' . $nfse->invoiceId . '</a></td>';
                echo '<td>' . htmlspecialchars($nfse->clientName ?? '—') . '</td>';
                echo '<td>' . $this->formatMoney($nfse->total) . '</td>';
                echo '<td>' . $this->renderStatusBadge($nfse->status) . '</td>';
                echo '<td><small>' . ($nfse->updatedAt ?? '—') . '</small></td>';
                echo '<td>' . $this->renderRowActions($nfse, $modulelink, true) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<div class="panel-body text-center text-muted">'
                . '<i class="fas fa-inbox fa-2x" style="margin-bottom:8px;display:block;"></i>'
                . 'Nenhuma NFS-e registrada ainda.</div>';
        }

        echo '</div>';
    }

    // ── Listagem ──────────────────────────────────────────────────────

    private function renderList(array $vars): void
    {
        $modulelink = $vars['modulelink'];
        $this->renderNav($modulelink, 'list');
        $this->renderStyles();

        $filters = [];
        if (!empty($_GET['filter_status']))  $filters['status']      = $_GET['filter_status'];
        if (!empty($_GET['filter_invoice'])) $filters['id_invoice']  = $_GET['filter_invoice'];
        if (!empty($_GET['filter_client']))  $filters['client_name'] = $_GET['filter_client'];

        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $result = $this->repository->search($filters, 'id', 'desc', $limit, $offset);

        // ── Feedback de exclusão ──
        if (!empty($_GET['delete']) && isset($_GET['delete_status'])) {
            $delId = (int) $_GET['delete'];
            if ($_GET['delete_status'] === '1') {
                echo '<div class="alert alert-success" style="margin-top:12px;">'
                    . '<i class="fas fa-check-circle"></i> '
                    . 'Registro <strong>#' . $delId . '</strong> excluído com sucesso.</div>';
            } else {
                echo '<div class="alert alert-danger" style="margin-top:12px;">'
                    . '<i class="fas fa-times-circle"></i> '
                    . 'Falha ao excluir o registro <strong>#' . $delId . '</strong>.</div>';
            }
        }

        // ── Filtros ──
        echo '<div class="panel panel-default" style="margin-top:14px;">';
        echo '<div class="panel-heading nfse-panel-heading"><i class="fas fa-filter"></i> Filtros</div>';
        echo '<div class="panel-body">';
        echo '<form method="get" class="form-inline" style="gap:6px;display:flex;flex-wrap:wrap;align-items:center;">';
        echo '<input type="hidden" name="module" value="nfsenacional">';
        echo '<input type="hidden" name="action" value="list">';

        echo '<div class="form-group">'
            . '<label class="sr-only">Fatura</label>'
            . '<input type="text" name="filter_invoice" class="form-control input-sm" placeholder="Nº da Fatura" '
            . 'value="' . htmlspecialchars($_GET['filter_invoice'] ?? '') . '">'
            . '</div>';

        echo '<div class="form-group">'
            . '<label class="sr-only">Cliente</label>'
            . '<input type="text" name="filter_client" class="form-control input-sm" placeholder="Nome do Cliente" '
            . 'value="' . htmlspecialchars($_GET['filter_client'] ?? '') . '">'
            . '</div>';

        echo '<div class="form-group"><select name="filter_status" class="form-control input-sm">'
            . '<option value="">Todos os status</option>';
        foreach (['PENDENTE', 'PROCESSANDO', 'AUTORIZADA', 'CANCELADA', 'ERRO'] as $s) {
            $sel = ($_GET['filter_status'] ?? '') === $s ? ' selected' : '';
            echo '<option value="' . $s . '"' . $sel . '>' . ucfirst(strtolower($s)) . '</option>';
        }
        echo '</select></div>';

        echo '<button type="submit" class="btn btn-primary btn-sm">'
            . '<i class="fas fa-search"></i> Pesquisar</button>';

        if (!empty($filters)) {
            $clearUrl = $modulelink . '&action=list';
            echo ' <a href="' . $clearUrl . '" class="btn btn-default btn-sm">'
                . '<i class="fas fa-times"></i> Limpar</a>';
        }

        echo '</form></div></div>';

        // ── Tabela ──
        $total = $result['total'] ?? 0;
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading nfse-panel-heading">'
            . '<i class="fas fa-list"></i> NFS-e encontradas'
            . ' <span class="badge">' . $total . '</span>'
            . '</div>';

        echo '<table class="table table-striped table-hover nfse-table" style="margin-bottom:0;">';
        echo '<thead><tr>'
            . '<th>ID</th>'
            . '<th>Fatura</th>'
            . '<th>Cliente</th>'
            . '<th>NFS-e</th>'
            . '<th>Valor</th>'
            . '<th>Status</th>'
            . '<th>Ambiente</th>'
            . '<th>Atualizado em</th>'
            . '<th style="min-width:130px;">Ações</th>'
            . '</tr></thead><tbody>';

        foreach ($result['data'] as $nfse) {
            $rowClass = $nfse->status === NfseStatus::ERRO ? 'danger' : '';
            echo '<tr class="' . $rowClass . '">';
            echo '<td><small class="text-muted">#' . $nfse->id . '</small></td>';
            echo '<td><a href="invoices.php?action=edit&id=' . $nfse->invoiceId . '">#' . $nfse->invoiceId . '</a></td>';
            echo '<td>' . htmlspecialchars($nfse->clientName ?? '—') . '</td>';
            echo '<td><small>' . htmlspecialchars($nfse->numeroNfseNacional ?? '—') . '</small></td>';
            echo '<td>' . $this->formatMoney($nfse->total) . '</td>';
            echo '<td>' . $this->renderStatusBadge($nfse->status) . '</td>';
            echo '<td>' . $this->renderAmbienteBadge($nfse->ambiente) . '</td>';
            echo '<td><small>' . ($nfse->updatedAt ?? '—') . '</small></td>';
            echo '<td>' . $this->renderRowActions($nfse, $modulelink) . '</td>';
            echo '</tr>';
        }

        if (empty($result['data'])) {
            echo '<tr><td colspan="9" class="text-center text-muted" style="padding:24px;">'
                . '<i class="fas fa-inbox fa-lg" style="margin-right:6px;"></i>'
                . 'Nenhuma NFS-e encontrada.</td></tr>';
        }

        echo '</tbody></table>';

        // ── Paginação ──
        $totalPages = (int) ceil($total / $limit);
        if ($totalPages > 1) {
            $baseUrl = $modulelink . '&action=list'
                . (isset($_GET['filter_status'])  ? '&filter_status='  . urlencode($_GET['filter_status'])  : '')
                . (isset($_GET['filter_invoice']) ? '&filter_invoice=' . urlencode($_GET['filter_invoice']) : '')
                . (isset($_GET['filter_client'])  ? '&filter_client='  . urlencode($_GET['filter_client'])  : '');

            echo '<div class="panel-footer" style="text-align:center;">';
            echo '<ul class="pagination pagination-sm" style="margin:6px 0;">';

            if ($page > 1) {
                echo '<li><a href="' . $baseUrl . '&page=' . ($page - 1) . '">&laquo;</a></li>';
            }
            $from = max(1, $page - 3);
            $to   = min($totalPages, $page + 3);
            for ($i = $from; $i <= $to; $i++) {
                $active = $i === $page ? ' class="active"' : '';
                echo '<li' . $active . '><a href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
            }
            if ($page < $totalPages) {
                echo '<li><a href="' . $baseUrl . '&page=' . ($page + 1) . '">&raquo;</a></li>';
            }

            echo '</ul>';
            echo '<small class="text-muted">Página ' . $page . ' de ' . $totalPages
                . ' — ' . $total . ' registros</small>';
            echo '</div>';
        }

        echo '</div>';
    }

    // ── Detalhe ───────────────────────────────────────────────────────

    private function renderDetail(array $vars): void
    {
        $modulelink = $vars['modulelink'];
        $this->renderNav($modulelink, 'detail');
        $this->renderStyles();

        $id   = (int) ($_GET['id'] ?? 0);
        $nfse = $this->repository->findById($id);

        if ($nfse === null) {
            echo '<div class="alert alert-warning" style="margin-top:14px;">'
                . '<i class="fas fa-exclamation-triangle"></i> NFS-e não encontrada.</div>';
            return;
        }

        // ── Cabeçalho ──
        echo '<div style="margin-top:14px;display:flex;align-items:center;gap:10px;margin-bottom:14px;">';
        echo '<h4 style="margin:0;">NFS-e <strong>#' . $nfse->id . '</strong></h4>';
        echo $this->renderStatusBadge($nfse->status, 'font-size:13px;padding:4px 10px;');
        echo $this->renderAmbienteBadge($nfse->ambiente, 'font-size:13px;');
        echo '</div>';

        // ── Botões de ação ──
        echo '<div style="margin-bottom:16px;display:flex;gap:6px;flex-wrap:wrap;">';
        echo $this->renderDetailActions($nfse, $modulelink);
        echo ' <a href="' . $modulelink . '&action=list" class="btn btn-default btn-sm">'
            . '<i class="fas fa-arrow-left"></i> Voltar</a>';
        echo '</div>';

        // ── Painel: dados principais ──
        echo '<div class="row">';

        // Coluna principal
        echo '<div class="col-sm-8">';
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading nfse-panel-heading"><i class="fas fa-file-invoice"></i> Dados da NFS-e</div>';
        echo '<table class="table table-bordered nfse-detail-table" style="margin-bottom:0;">';

        $mainFields = [
            'id_invoice'           => $nfse->invoiceId
                ? '<a href="invoices.php?action=edit&id=' . $nfse->invoiceId . '">#' . $nfse->invoiceId . '</a>'
                : '—',
            'client_name'          => htmlspecialchars($nfse->clientName ?? '—'),
            'total'                => $this->formatMoney($nfse->total),
            'numero_nfse_nacional'  => htmlspecialchars($nfse->numeroNfseNacional ?? '—'),
            'numero_dps'           => $nfse->numeroDps ? $nfse->serieDps . '-' . $nfse->numeroDps : '—',
            'chave_acesso'         => $nfse->chaveAcesso
                ? '<code style="word-break:break-all;font-size:11px;">' . htmlspecialchars($nfse->chaveAcesso) . '</code>'
                : '—',
            'protocolo'            => htmlspecialchars($nfse->protocolo ?? '—'),
            'codigo_verificacao'   => htmlspecialchars($nfse->codigoVerificacao ?? '—'),
            'data_emissao'         => $nfse->dataEmissao ?? '—',
            'data_autorizacao'     => $nfse->dataAutorizacao ?? '—',
        ];

        foreach ($mainFields as $key => $value) {
            $label = self::FIELD_LABELS[$key] ?? $key;
            echo '<tr>'
                . '<th style="width:38%;background:#fafafa;font-weight:600;color:#555;">' . $label . '</th>'
                . '<td>' . $value . '</td>'
                . '</tr>';
        }

        echo '</table></div>';

        // Erro (se houver)
        if (!empty($nfse->erro)) {
            echo '<div class="panel panel-danger">';
            echo '<div class="panel-heading"><i class="fas fa-exclamation-triangle"></i> Mensagem de Erro</div>';
            echo '<div class="panel-body" style="font-family:monospace;font-size:12px;word-break:break-word;">'
                . htmlspecialchars($nfse->erro) . '</div>';
            echo '</div>';
        }

        echo '</div>';

        // Coluna lateral
        echo '<div class="col-sm-4">';

        // Links de acesso
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading nfse-panel-heading"><i class="fas fa-link"></i> Links</div>';
        echo '<div class="panel-body">';
        if (!empty($nfse->danfseUrl)) {
            echo '<a href="' . htmlspecialchars($nfse->danfseUrl) . '" target="_blank" '
                . 'class="btn btn-success btn-block btn-sm" style="margin-bottom:6px;">'
                . '<i class="fas fa-file-pdf"></i> Ver DANFS-e</a>';
        } else {
            echo '<p class="text-muted text-center"><small>DANFS-e não disponível</small></p>';
        }
        if (!empty($nfse->xmlUrl)) {
            echo '<a href="' . htmlspecialchars($nfse->xmlUrl) . '" target="_blank" '
                . 'class="btn btn-default btn-block btn-sm">'
                . '<i class="fas fa-code"></i> Baixar XML</a>';
        }
        echo '</div></div>';

        // Informações de sistema
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading nfse-panel-heading"><i class="fas fa-info-circle"></i> Sistema</div>';
        echo '<table class="table" style="margin-bottom:0;font-size:12px;">';
        foreach ([
            'id'         => '#' . $nfse->id,
            'id_client'  => '<a href="clientssummary.php?userid=' . $nfse->clientId . '">#' . $nfse->clientId . '</a>',
            'ambiente'   => $this->renderAmbienteBadge($nfse->ambiente),
            'created_at' => $nfse->createdAt ?? '—',
            'updated_at' => $nfse->updatedAt ?? '—',
        ] as $key => $value) {
            $label = self::FIELD_LABELS[$key] ?? $key;
            echo '<tr>'
                . '<th style="color:#777;font-weight:600;">' . $label . '</th>'
                . '<td>' . $value . '</td>'
                . '</tr>';
        }
        echo '</table></div>';

        echo '</div>'; // col-sm-4
        echo '</div>'; // row
    }

    // ── Helpers de renderização ──────────────────────────────────────

    /**
     * Renderiza o badge de status colorido.
     */
    private function renderStatusBadge(NfseStatus $status, string $extraStyle = ''): string
    {
        $colors = [
            NfseStatus::PENDENTE->value    => ['bg' => '#aaa',     'icon' => 'fa-clock'],
            NfseStatus::PROCESSANDO->value => ['bg' => '#5bc0de',  'icon' => 'fa-sync-alt'],
            NfseStatus::AUTORIZADA->value  => ['bg' => '#5cb85c',  'icon' => 'fa-check-circle'],
            NfseStatus::CANCELADA->value   => ['bg' => '#d9534f',  'icon' => 'fa-ban'],
            NfseStatus::SUBSTITUIDA->value => ['bg' => '#f0ad4e',  'icon' => 'fa-exchange-alt'],
            NfseStatus::ERRO->value        => ['bg' => '#d9534f',  'icon' => 'fa-exclamation-triangle'],
        ];

        $cfg = $colors[$status->value] ?? ['bg' => '#aaa', 'icon' => 'fa-circle'];
        return '<span style="display:inline-block;background:' . $cfg['bg'] . ';color:#fff;'
            . 'padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700;' . $extraStyle . '">'
            . '<i class="fas ' . $cfg['icon'] . '" style="margin-right:3px;"></i>'
            . $status->label()
            . '</span>';
    }

    /**
     * Renderiza o badge de ambiente.
     */
    private function renderAmbienteBadge(?string $ambiente, string $extraStyle = ''): string
    {
        if (empty($ambiente)) return '<span class="text-muted">—</span>';

        $isProd = strtolower($ambiente) === 'producao';
        $bg     = $isProd ? '#d9534f' : '#5bc0de';
        $label  = $isProd ? 'Produção' : 'Homologação';
        return '<span style="display:inline-block;background:' . $bg . ';color:#fff;'
            . 'padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700;' . $extraStyle . '">'
            . $label . '</span>';
    }

    /**
     * Renderiza botões de ação para uma linha da tabela.
     */
    private function renderRowActions(Nfse $nfse, string $modulelink, bool $compact = false): string
    {
        $html = '<div style="display:flex;gap:3px;flex-wrap:wrap;">';

        // Ver detalhe
        $html .= '<a href="' . $modulelink . '&action=detail&id=' . $nfse->id . '" '
            . 'class="btn btn-default btn-xs" title="Ver detalhes" data-toggle="tooltip">'
            . '<i class="fas fa-eye"></i>' . ($compact ? '' : ' Ver') . '</a>';

        // Excluir (apenas não-autorizadas)
        if (!$nfse->isAutorizada()) {
            $tokenDel = hash_hmac('sha1', (string) $nfse->id, 'nfsenacional');
            $html .= '<a href="' . $modulelink . '&action=excluir&delete=' . $nfse->id . '&token=' . $tokenDel . '" '
                . 'class="btn btn-danger btn-xs" title="Excluir registro" data-toggle="tooltip" '
                . 'onclick="return confirm(\'Excluir NFS-e ID ' . $nfse->id . ' da fatura #' . $nfse->invoiceId . '?\')">'
                . '<i class="fas fa-trash-alt"></i></a>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza botões de ação para a tela de detalhe.
     */
    private function renderDetailActions(Nfse $nfse, string $modulelink): string
    {
        $html = '';

        if ($nfse->invoiceId) {
            $invoiceId = (int) $nfse->invoiceId;
            $token     = hash_hmac('sha1', (string) $invoiceId, 'nfsenacional');

            // Emitir (PENDENTE ou ERRO)
            if (in_array($nfse->status, [NfseStatus::PENDENTE, NfseStatus::ERRO], true)) {
                $html .= '<a href="' . $modulelink . '&action=emitir&invoiceid=' . $invoiceId . '&token=' . $token . '" '
                    . 'class="btn btn-success btn-sm" '
                    . 'onclick="return confirm(\'Emitir NFS-e para a fatura #' . $invoiceId . '?\')">'
                    . '<i class="fas fa-paper-plane"></i> Emitir NFS-e</a>';
            }

            // Cancelar (AUTORIZADA)
            if ($nfse->podeCancelar()) {
                $html .= '<a href="' . $modulelink . '&action=cancelar&invoiceid=' . $invoiceId . '&token=' . $token . '" '
                    . 'class="btn btn-warning btn-sm" '
                    . 'onclick="return confirm(\'Cancelar a NFS-e da fatura #' . $invoiceId . '? Esta ação não pode ser desfeita.\')">'
                    . '<i class="fas fa-ban"></i> Cancelar NFS-e</a>';
            }

            // Reenviar e-mail (AUTORIZADA)
            if ($nfse->isAutorizada()) {
                $tokenEmail = hash_hmac('sha1', 'email_' . $invoiceId, 'nfsenacional');
                $html .= '<a href="' . $modulelink . '&action=reenviar_email&invoiceid=' . $invoiceId . '&token=' . $tokenEmail . '" '
                    . 'class="btn btn-info btn-sm">'
                    . '<i class="fas fa-envelope"></i> Reenviar E-mail</a>';
            }
        }

        // Excluir (não-autorizadas)
        if (!$nfse->isAutorizada()) {
            $tokenDel = hash_hmac('sha1', (string) $nfse->id, 'nfsenacional');
            $html .= '<a href="' . $modulelink . '&action=excluir&delete=' . $nfse->id . '&token=' . $tokenDel . '" '
                . 'class="btn btn-danger btn-sm" '
                . 'onclick="return confirm(\'Excluir definitivamente este registro NFS-e #' . $nfse->id . '?\')">'
                . '<i class="fas fa-trash-alt"></i> Excluir Registro</a>';
        }

        return $html;
    }

    /**
     * Formata valor monetário.
     */
    private function formatMoney(?float $value): string
    {
        if ($value === null) return '—';
        return '<span style="font-weight:600;">R$&nbsp;' . number_format($value, 2, ',', '.') . '</span>';
    }

    // ── Navegação ─────────────────────────────────────────────────────

    private function renderNav(string $modulelink, string $activeAction): void
    {
        echo '<div class="nfse-nav-bar">';
        echo '<div style="display:flex;align-items:center;gap:8px;">';

        // Logo / título
        echo '<span style="font-size:15px;font-weight:700;color:#333;margin-right:6px;">'
            . '<i class="fas fa-file-invoice" style="color:#1a7abf;margin-right:5px;"></i>'
            . 'NFS-e Nacional</span>';

        echo '<ul class="nav nav-pills nav-sm" style="margin:0;">';

        $items = [
            ''       => ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
            'list'   => ['icon' => 'fa-list',           'label' => 'Todas as NFS-e'],
        ];

        foreach ($items as $action => $item) {
            $active = $activeAction === $action ? ' class="active"' : '';
            $href   = $action === '' ? $modulelink : $modulelink . '&action=' . $action;
            echo '<li role="presentation"' . $active . '>'
                . '<a href="' . $href . '"><i class="fas ' . $item['icon'] . '"></i> ' . $item['label'] . '</a>'
                . '</li>';
        }

        echo '</ul></div>';

        // Link configurações (direita)
        echo '<div style="margin-left:auto;">';
        echo '<a href="configaddonmods.php?saved=true#nfsenacional" class="btn btn-default btn-sm">'
            . '<i class="fas fa-cogs"></i> Configurações</a>';
        echo '</div>';

        echo '</div>'; // nfse-nav-bar
    }

    // ── Estilos ───────────────────────────────────────────────────────

    private function renderStyles(): void
    {
        echo <<<'CSS'
<style>
/* ── Navegação ── */
.nfse-nav-bar {
    display: flex;
    align-items: center;
    padding: 8px 0 10px;
    border-bottom: 2px solid #1a7abf;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 8px;
}

/* ── Cards de estatística ── */
.nfse-stat-row { margin-top: 0; }
.nfse-stat-card { transition: box-shadow .2s; border-radius: 4px; }
.nfse-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.nfse-stat-card .panel-body { text-align: center; padding: 16px 12px; }
.nfse-stat-icon { font-size: 28px; margin-bottom: 6px; }
.nfse-stat-value { font-size: 2.4em; font-weight: 700; line-height: 1.1; }
.nfse-stat-label { font-size: 11px; color: #888; margin-top: 4px; text-transform: uppercase; letter-spacing: .4px; }

/* ── Cabeçalhos de painel ── */
.nfse-panel-heading {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: .2px;
}

/* ── Tabelas ── */
.nfse-table thead th {
    background: #f5f5f5;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    color: #555;
    white-space: nowrap;
    border-bottom: 2px solid #ddd;
}
.nfse-table tbody tr:hover { background: #f0f7ff !important; }

/* ── Tabela de detalhe ── */
.nfse-detail-table th {
    white-space: nowrap;
    vertical-align: middle;
    font-size: 12px;
}
.nfse-detail-table td { vertical-align: middle; }

/* ── Tooltips Bootstrap ── */
[data-toggle="tooltip"] { cursor: pointer; }
</style>
<script>
jQuery(function ($) {
    $('[data-toggle="tooltip"]').tooltip({ container: 'body', trigger: 'hover' });
});
</script>
CSS;
    }
}
