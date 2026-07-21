<?php

namespace GK2\NfseNacional\Hook;

use GK2\NfseNacional\Domain\AmbienteGuard;
use WHMCS\Database\Capsule;

/**
 * Injeta ícone de NFS-e ao lado de cada fatura na área do cliente.
 */
class ClientInvoiceListUI
{
    private const TABLE = 'tblnfsenacional';

    /** Actions do clientarea.php onde links de fatura aparecem. */
    private const ALLOWED_ACTIONS = ['invoices', 'viewinvoice', ''];

    public function getScript(array $vars): string
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== 'clientarea.php') {
            return '';
        }

        $action = $_GET['action'] ?? '';
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return '';
        }

        try {
            $clientId = (int) ($_SESSION['uid'] ?? 0);
            if ($clientId <= 0) {
                return '';
            }

            $ambiente = AmbienteGuard::getInstance()->value();

            $rows = Capsule::table(self::TABLE)
                ->where('id_client', $clientId)
                ->where('ambiente', $ambiente)
                ->select('id_invoice', 'status')
                ->get();

            if ($rows->isEmpty()) {
                return '';
            }

            $map = [];
            foreach ($rows as $row) {
                $map[(int) $row->id_invoice] = $row->status;
            }

            $mapJson = json_encode($map, JSON_THROW_ON_ERROR);
            $nfseUrl = 'index.php?m=nfsenacional&search=';
        } catch (\Throwable $e) {
            return '';
        }

        return <<<HTML
<script>
(function () {
    var nfseMap  = {$mapJson};
    var nfseBase = '{$nfseUrl}';

    var nfseColors = {
        'AUTORIZADA':  {bg:'#e8f5e9', fg:'#2e7d32', border:'#c8e6c9'},
        'CANCELADA':   {bg:'#fff3e0', fg:'#e65100', border:'#ffe0b2'},
        'ERRO':        {bg:'#ffebee', fg:'#c62828', border:'#ffcdd2'},
        'PROCESSANDO': {bg:'#e3f2fd', fg:'#1565c0', border:'#bbdefb'},
        'PENDENTE':    {bg:'#f5f5f5', fg:'#757575', border:'#e0e0e0'},
        'SUBSTITUIDA': {bg:'#efebe9', fg:'#4e342e', border:'#d7ccc8'}
    };

    var nfseIcons = {
        'AUTORIZADA':  'fa-check-circle',
        'CANCELADA':   'fa-ban',
        'ERRO':        'fa-exclamation-circle',
        'PROCESSANDO': 'fa-sync-alt',
        'PENDENTE':    'fa-clock',
        'SUBSTITUIDA': 'fa-exchange-alt'
    };

    function buildBadge(status, invoiceId) {
        var c    = nfseColors[status] || {bg:'#f5f5f5', fg:'#888', border:'#e0e0e0'};
        var icon = nfseIcons[status]  || 'fa-file-invoice';
        var label = status.charAt(0) + status.slice(1).toLowerCase();
        var a    = document.createElement('a');
        a.className = 'nfse-client-badge';
        a.href  = nfseBase + invoiceId;
        a.title = 'Ver NFS-e desta fatura';
        a.style.cssText = [
            'display:inline-flex', 'align-items:center', 'gap:3px',
            'margin-left:6px', 'padding:1px 6px', 'border-radius:3px',
            'font-size:10px', 'font-weight:700', 'vertical-align:middle',
            'text-decoration:none', 'cursor:pointer', 'white-space:nowrap',
            'background:' + c.bg, 'color:' + c.fg, 'border:1px solid ' + c.border
        ].join(';');
        a.innerHTML = '<i class="fas ' + icon + '" style="font-size:10px;"></i> NFS-e';
        a.addEventListener('click', function(e){ e.stopPropagation(); });
        return a;
    }

    function addIcons() {
        /* Tabela de faturas do cliente: cada <tr data-url="viewinvoice.php?id=X"> */
        var rows = document.querySelectorAll('tr[data-url*="viewinvoice.php?id="]');
        rows.forEach(function (tr) {
            var match = tr.getAttribute('data-url').match(/[?&]id=(\d+)/);
            if (!match) return;
            var invoiceId = parseInt(match[1], 10);
            if (!Object.prototype.hasOwnProperty.call(nfseMap, invoiceId)) return;

            var td = tr.querySelector('td.dtr-control');
            if (!td || td.querySelector('.nfse-client-badge')) return;
            td.appendChild(buildBadge(nfseMap[invoiceId], invoiceId));
        });

        /* Fallback: links diretos em outros contextos */
        document.querySelectorAll('a[href*="viewinvoice.php?id="]:not([data-nfse-checked])').forEach(function (link) {
            link.setAttribute('data-nfse-checked', '1');
            var match = link.href.match(/[?&]id=(\d+)/);
            if (!match) return;
            var invoiceId = parseInt(match[1], 10);
            if (!Object.prototype.hasOwnProperty.call(nfseMap, invoiceId)) return;
            if (link.parentNode.querySelector('.nfse-client-badge')) return;
            link.insertAdjacentElement('afterend', buildBadge(nfseMap[invoiceId], invoiceId));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addIcons);
    } else {
        addIcons();
    }
})();
</script>
HTML;
    }
}
