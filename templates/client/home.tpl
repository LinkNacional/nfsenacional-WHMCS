<style>
.nfse-client-wrap { max-width: 960px; margin: 0 auto; font-family: inherit; }

.nfse-client-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 8px;
}
.nfse-client-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 8px;
}
.nfse-client-title i { font-size: 20px; }
.nfse-client-count { font-size: 13px; color: #888; }

/* Barra de busca */
.nfse-search-form { margin-bottom: 14px; }
.nfse-search-wrap {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
    max-width: 480px;
}
.nfse-search-icon { padding: 0 10px; color: #bbb; font-size: 13px; flex-shrink: 0; }
.nfse-search-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 8px 4px;
    font-size: 13px;
    background: transparent;
    min-width: 0;
}
.nfse-search-clear { padding: 0 10px; color: #bbb; font-size: 12px; text-decoration: none; flex-shrink: 0; display:none; }
.nfse-search-clear:hover { color: #e53935; }
.nfse-search-clear.visible { display: inline-flex; }
.nfse-search-btn {
    background: #3949ab;
    color: #fff;
    border: none;
    padding: 0 16px;
    height: 38px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s;
}
.nfse-search-btn:hover { background: #283593; }

/* Tabela */
.nfse-table-wrap {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    overflow: hidden;
}
.nfse-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.nfse-table thead tr {
    background: #f5f6fa;
    border-bottom: 2px solid #e8e8e8;
}
.nfse-table th {
    padding: 10px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #666;
    white-space: nowrap;
}
.nfse-table th.sortable { cursor: pointer; user-select: none; }
.nfse-table th.sortable:hover { color: #3949ab; }
.nfse-table th.sort-active { color: #3949ab; }
.nfse-sort-icon { font-size: 10px; opacity: .5; margin-left: 4px; }
.nfse-table th.sort-active .nfse-sort-icon { opacity: 1; }

.nfse-table tbody tr { border-bottom: 1px solid #f0f0f0; transition: background .1s; }
.nfse-table tbody tr:last-child { border-bottom: none; }
.nfse-table tbody tr:hover { background: #fafbff; }

.nfse-table td { padding: 12px 14px; vertical-align: middle; }

.nfse-td-num  { font-size: 15px; font-weight: 700; color: #1a237e; white-space: nowrap; }
.nfse-td-invoice a { color: #3949ab; text-decoration: none; font-weight: 600; }
.nfse-td-invoice a:hover { text-decoration: underline; }
.nfse-td-date  { color: #777; white-space: nowrap; }
.nfse-td-valor { font-weight: 700; color: #222; white-space: nowrap; }
.nfse-td-actions { white-space: nowrap; }

/* Badge de status */
.nfse-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.nfse-badge-AUTORIZADA  { background: #e8f5e9; color: #2e7d32; }
.nfse-badge-CANCELADA   { background: #fff3e0; color: #e65100; }
.nfse-badge-ERRO        { background: #ffebee; color: #b71c1c; }
.nfse-badge-PROCESSANDO { background: #e3f2fd; color: #1565c0; }
.nfse-badge-PENDENTE    { background: #f5f5f5; color: #757575; }
.nfse-badge-SUBSTITUIDA { background: #efebe9; color: #4e342e; }

/* Botões de ação */
.nfse-doc-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    text-decoration: none;
    transition: opacity .15s;
    border: 1px solid transparent;
    margin-right: 4px;
    cursor: pointer;
}
.nfse-doc-link:last-child { margin-right: 0; }
.nfse-doc-link:hover { opacity: .8; text-decoration: none; }
.nfse-doc-link-pdf   { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
.nfse-doc-link-xml   { background: #e8eaf6; color: #3949ab; border-color: #c5cae9; }
.nfse-doc-link-email { background: #f3e5f5; color: #7b1fa2; border-color: #e1bee7; }
.nfse-doc-link-email.loading { opacity: .6; pointer-events: none; }

/* Estado vazio */
.nfse-empty-state {
    text-align: center;
    padding: 48px 20px;
    color: #bbb;
    border: 1px dashed #e0e0e0;
    border-radius: 6px;
}
.nfse-empty-state i { font-size: 36px; display: block; margin-bottom: 12px; }
.nfse-empty-state p { font-size: 14px; margin: 0; }

/* Loading skeleton */
.nfse-loading {
    text-align: center;
    padding: 40px;
    color: #aaa;
    font-size: 14px;
}
.nfse-loading i { margin-right: 8px; }

/* Paginação */
.nfse-pagination {
    display: flex;
    gap: 4px;
    justify-content: center;
    margin-top: 20px;
    flex-wrap: wrap;
}
.nfse-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    color: #3949ab;
    text-decoration: none;
    background: #fff;
    transition: background .12s, border-color .12s;
    cursor: pointer;
}
.nfse-page-link:hover { background: #e8eaf6; border-color: #c5cae9; text-decoration: none; }
.nfse-page-link.active { background: #3949ab; color: #fff; border-color: #3949ab; pointer-events: none; }

/* Toast de feedback */
.nfse-toast {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9999;
    padding: 12px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
    display: flex;
    align-items: center;
    gap: 8px;
    opacity: 0;
    transform: translateY(-8px);
    transition: opacity .25s, transform .25s;
    pointer-events: none;
}
.nfse-toast.show { opacity: 1; transform: translateY(0); }
.nfse-toast-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.nfse-toast-error   { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }

@media (max-width: 680px) {
    .nfse-table th.hide-sm,
    .nfse-table td.hide-sm { display: none; }
    .nfse-search-wrap { max-width: 100%; }
    .nfse-toast { top: auto; bottom: 16px; right: 16px; left: 16px; }
}
</style>

<div id="nfse-toast" class="nfse-toast" role="alert" aria-live="polite"></div>

<div class="nfse-client-wrap">

    <div class="nfse-client-header">
        <div class="nfse-client-title">
            <i class="fas fa-file-invoice-dollar"></i>
            Notas Fiscais Eletrônicas
        </div>
        <span id="nfse-count" class="nfse-client-count"></span>
    </div>

    <form id="nfse-search-form" class="nfse-search-form" onsubmit="return false;">
        <div class="nfse-search-wrap">
            <i class="fas fa-search nfse-search-icon"></i>
            <input type="text" id="nfse-search-input" class="nfse-search-input"
                   placeholder="Buscar por Nº NFS-e ou Nº fatura…"
                   value="{$initial_search|escape:'html'}">
            <a id="nfse-search-clear" href="#" class="nfse-search-clear{if $initial_search} visible{/if}" title="Limpar busca">
                <i class="fas fa-times"></i>
            </a>
            <button type="submit" class="nfse-search-btn">Buscar</button>
        </div>
    </form>

    <div id="nfse-content">
        <div class="nfse-loading">
            <i class="fas fa-spinner fa-spin"></i> Carregando…
        </div>
    </div>

    <nav id="nfse-pagination" class="nfse-pagination" style="display:none;"></nav>

</div>

<script>
(function () {
    var MODULELINK = '{$modulelink|escape:'javascript'}';
    var AJAX_BASE  = MODULELINK + '&ajax=1';

    var state = {
        page:    {$initial_page|intval},
        orderby: '{$initial_orderby|escape:'javascript'}',
        dir:     '{$initial_dir|escape:'javascript'}',
        search:  '{$initial_search|escape:'javascript'}',
        loading: false
    };

    var badgeIcons = {
        AUTORIZADA:  'fa-check-circle',
        CANCELADA:   'fa-ban',
        ERRO:        'fa-exclamation-circle',
        PROCESSANDO: 'fa-sync-alt',
        PENDENTE:    'fa-clock',
        SUBSTITUIDA: 'fa-exchange-alt'
    };

    // ── Elementos do DOM ──────────────────────────────────────────────────────

    var elContent    = document.getElementById('nfse-content');
    var elPagination = document.getElementById('nfse-pagination');
    var elCount      = document.getElementById('nfse-count');
    var elSearch     = document.getElementById('nfse-search-input');
    var elClear      = document.getElementById('nfse-search-clear');
    var elForm       = document.getElementById('nfse-search-form');
    var elToast      = document.getElementById('nfse-toast');

    // ── Toast ─────────────────────────────────────────────────────────────────

    var toastTimer;
    function showToast(msg, type) {
        elToast.className = 'nfse-toast nfse-toast-' + (type || 'success');
        elToast.innerHTML = '<i class="fas ' + (type === 'error' ? 'fa-times-circle' : 'fa-check-circle') + '"></i> ' + msg;
        elToast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            elToast.classList.remove('show');
        }, 4000);
    }

    // ── URL da história do navegador ──────────────────────────────────────────

    function buildPageUrl() {
        var url = MODULELINK;
        if (state.search) url += '&search=' + encodeURIComponent(state.search);
        url += '&orderby=' + state.orderby + '&dir=' + state.dir;
        if (state.page > 1) url += '&page=' + state.page;
        return url;
    }

    // ── Fetch de dados ────────────────────────────────────────────────────────

    function loadData() {
        if (state.loading) return;
        state.loading = true;

        elContent.innerHTML = '<div class="nfse-loading"><i class="fas fa-spinner fa-spin"></i> Carregando…</div>';
        elPagination.style.display = 'none';

        var url = AJAX_BASE
            + '&page='    + state.page
            + '&orderby=' + encodeURIComponent(state.orderby)
            + '&dir='     + state.dir
            + '&search='  + encodeURIComponent(state.search);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function () {
            state.loading = false;
            if (xhr.status !== 200) {
                elContent.innerHTML = '<div class="nfse-empty-state"><i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar dados.</p></div>';
                return;
            }
            var data = JSON.parse(xhr.responseText);
            render(data);
            history.replaceState(null, '', buildPageUrl());
        };
        xhr.onerror = function () {
            state.loading = false;
            elContent.innerHTML = '<div class="nfse-empty-state"><i class="fas fa-exclamation-triangle"></i><p>Erro de conexão.</p></div>';
        };
        xhr.send();
    }

    // ── Renderização ──────────────────────────────────────────────────────────

    function render(data) {
        // Contador
        if (data.total > 0) {
            elCount.textContent = data.total + (data.total === 1 ? ' nota encontrada' : ' notas encontradas');
        } else {
            elCount.textContent = '';
        }

        if (!data.notas || data.notas.length === 0) {
            var emptyMsg = state.search
                ? 'Nenhuma nota encontrada para "<strong>' + escHtml(state.search) + '</strong>".'
                : 'Nenhuma nota fiscal eletrônica encontrada.';
            elContent.innerHTML = '<div class="nfse-empty-state"><i class="fas fa-file-invoice"></i><p>' + emptyMsg + '</p></div>';
            elPagination.style.display = 'none';
            return;
        }

        // Tabela
        var html = '<div class="nfse-table-wrap"><table class="nfse-table"><thead><tr>';
        html += thSort('numero', 'Nº NFS-e', data.orderby, data.dir);
        html += thSort('fatura', 'Fatura',   data.orderby, data.dir);
        html += thSort('data',   'Data',     data.orderby, data.dir, 'hide-sm');
        html += thSort('valor',  'Valor',    data.orderby, data.dir, 'hide-sm');
        html += thSort('status', 'Status',   data.orderby, data.dir);
        html += '<th>Ações</th>';
        html += '</tr></thead><tbody>';

        data.notas.forEach(function (nota) {
            var statusRaw  = nota.status_raw || 'PENDENTE';
            var icon       = badgeIcons[statusRaw] || 'fa-clock';
            var dateLabel  = nota.data_autorizacao || nota.created_at || '—';

            html += '<tr>';
            html += '<td class="nfse-td-num">' + (nota.numero || '—') + '</td>';
            html += '<td class="nfse-td-invoice"><a href="viewinvoice.php?id=' + nota.invoice_id + '">#&thinsp;' + nota.invoice_id + '</a></td>';
            html += '<td class="nfse-td-date hide-sm">' + escHtml(dateLabel) + '</td>';
            html += '<td class="nfse-td-valor hide-sm">R$&thinsp;' + nota.total + '</td>';
            html += '<td><span class="nfse-badge nfse-badge-' + statusRaw + '"><i class="fas ' + icon + '"></i> ' + escHtml(nota.status) + '</span></td>';
            html += '<td class="nfse-td-actions">' + renderActions(nota) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        elContent.innerHTML = html;

        // Paginação
        renderPagination(data.total_pages, data.page);
    }

    function thSort(key, label, currentKey, currentDir, extraClass) {
        var active   = currentKey === key;
        var nextDir  = (active && currentDir === 'desc') ? 'asc' : 'desc';
        var iconCls  = active ? (currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
        var classes  = 'sortable' + (active ? ' sort-active' : '') + (extraClass ? ' ' + extraClass : '');
        return '<th class="' + classes + '" data-orderby="' + key + '" data-dir="' + nextDir + '">'
             + label + ' <i class="fas ' + iconCls + ' nfse-sort-icon"></i></th>';
    }

    function renderActions(nota) {
        var html = '';
        if (nota.danfse_url) {
            html += '<a href="' + nota.danfse_url + '" target="_blank" class="nfse-doc-link nfse-doc-link-pdf" title="Ver DANFS-e">'
                  + '<i class="fas fa-file-pdf"></i> DANFS-e</a>';
        }
        if (nota.xml_url) {
            html += '<a href="' + nota.xml_url + '" target="_blank" class="nfse-doc-link nfse-doc-link-xml" title="Baixar XML">'
                  + '<i class="fas fa-code"></i> XML</a>';
        }
        if (nota.pode_reenviar) {
            html += '<a href="#" class="nfse-doc-link nfse-doc-link-email nfse-reenviar" title="Reenviar por e-mail"'
                  + ' data-invoiceid="' + nota.invoice_id + '" data-token="' + nota.reenviar_token + '">'
                  + '<i class="fas fa-envelope"></i></a>';
        }
        return html;
    }

    function renderPagination(totalPages, page) {
        if (totalPages <= 1) {
            elPagination.style.display = 'none';
            return;
        }
        var html = '';
        for (var i = 1; i <= totalPages; i++) {
            html += '<a href="#" class="nfse-page-link' + (i === page ? ' active' : '') + '" data-page="' + i + '">' + i + '</a>';
        }
        elPagination.innerHTML = html;
        elPagination.style.display = 'flex';
    }

    // ── Reenviar email via AJAX ───────────────────────────────────────────────

    function reenviarEmail(invoiceId, token, btn) {
        btn.classList.add('loading');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        var url = AJAX_BASE + '&action=reenviar&invoiceid=' + invoiceId + '&token=' + encodeURIComponent(token);
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function () {
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="fas fa-envelope"></i>';
            if (xhr.status === 200) {
                var res = JSON.parse(xhr.responseText);
                if (res.sucesso) {
                    showToast('E-mail com a NFS-e enviado com sucesso.', 'success');
                } else {
                    showToast('Não foi possível enviar o e-mail: ' + (res.msg || 'Erro desconhecido.'), 'error');
                }
            } else {
                showToast('Erro ao processar a requisição.', 'error');
            }
        };
        xhr.onerror = function () {
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="fas fa-envelope"></i>';
            showToast('Erro de conexão.', 'error');
        };
        xhr.send();
    }

    // ── Utilitários ───────────────────────────────────────────────────────────

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Eventos ───────────────────────────────────────────────────────────────

    // Busca
    elForm.addEventListener('submit', function () {
        state.search = elSearch.value.trim();
        state.page   = 1;
        elClear.classList.toggle('visible', state.search.length > 0);
        loadData();
    });

    // Limpar busca
    elClear.addEventListener('click', function (e) {
        e.preventDefault();
        elSearch.value = '';
        state.search   = '';
        state.page     = 1;
        elClear.classList.remove('visible');
        loadData();
    });

    // Mostrar/esconder botão de limpar conforme digitação
    elSearch.addEventListener('input', function () {
        elClear.classList.toggle('visible', elSearch.value.length > 0);
    });

    // Cliques delegados: sort, paginação, reenviar
    document.addEventListener('click', function (e) {
        // Ordenação (clique no th)
        var th = e.target.closest('th.sortable');
        if (th) {
            state.orderby = th.getAttribute('data-orderby');
            state.dir     = th.getAttribute('data-dir');
            state.page    = 1;
            loadData();
            return;
        }

        // Paginação
        var pg = e.target.closest('.nfse-page-link');
        if (pg && pg.getAttribute('data-page')) {
            e.preventDefault();
            state.page = parseInt(pg.getAttribute('data-page'), 10);
            loadData();
            return;
        }

        // Reenviar email
        var btn = e.target.closest('.nfse-reenviar');
        if (btn) {
            e.preventDefault();
            reenviarEmail(btn.getAttribute('data-invoiceid'), btn.getAttribute('data-token'), btn);
        }
    });

    // ── Carga inicial ─────────────────────────────────────────────────────────
    loadData();

})();
</script>
