/**
 * NFS-e Nacional - Scripts
 */

(function() {
    'use strict';

    // Confirmacao de cancelamento
    document.addEventListener('DOMContentLoaded', function() {
        var cancelButtons = document.querySelectorAll('.nfse-cancel-btn');
        cancelButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (!confirm('Confirma o cancelamento da NFS-e Nacional?')) {
                    e.preventDefault();
                }
            });
        });
    });
})();
