<div class="nfse-nacional-client">
    <h2>Notas Fiscais Eletronicas (Nacional)</h2>

    {if $total > 0}
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tblNfseNacional">
                <thead>
                    <tr>
                        <th>Fatura</th>
                        <th>Numero NFS-e</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$notas item=nota}
                        <tr>
                            <td>
                                <a href="viewinvoice.php?id={$nota.invoice_id}">#{$nota.invoice_id}</a>
                            </td>
                            <td>{$nota.numero}</td>
                            <td>R$ {$nota.total}</td>
                            <td>
                                <span class="label {$nota.status_class}">{$nota.status}</span>
                            </td>
                            <td>{$nota.data_autorizacao}</td>
                            <td>
                                {if $nota.danfse_url}
                                    <a href="{$nota.danfse_url}" target="_blank" class="btn btn-xs btn-info" title="Ver DANFS-e">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                {/if}
                                {if $nota.xml_url}
                                    <a href="{$nota.xml_url}" target="_blank" class="btn btn-xs btn-default" title="Ver XML">
                                        <i class="fas fa-code"></i>
                                    </a>
                                {/if}
                                {if $nota.pode_reenviar}
                                    <a href="{$modulelink}&action=reenviar&invoiceid={$nota.invoice_id}&token={$nota.invoice_id|cat:':'|cat:$clientsdetails.id|sha1}" class="btn btn-xs btn-primary" title="Reenviar Email">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <script>
            jQuery(document).ready(function() {
                if (jQuery.fn.DataTable) {
                    jQuery('#tblNfseNacional').DataTable({
                        ordering: true,
                        order: [[0, 'desc']],
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                        }
                    });
                }
            });
        </script>
    {else}
        <div class="alert alert-info">
            Nenhuma nota fiscal eletronica encontrada.
        </div>
    {/if}
</div>
