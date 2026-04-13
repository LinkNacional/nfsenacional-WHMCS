<?php

namespace GK2\NfseNacional\Domain\Service;

use GK2\NfseNacional\Domain\Entity\Nfse;
use GK2\NfseNacional\Security\TokenSigner;
use WHMCS\Database\Capsule;

/**
 * Gera URLs assinadas para download de DANFS-e e XML via nosso endpoint proxy.
 *
 * O token HMAC garante que apenas URLs geradas pelo sistema são aceitas,
 * impedindo que um cliente acesse documentos de outros clientes por força bruta.
 */
class DownloadUrlService
{
    private string $baseUrl;

    public function __construct()
    {
        $sysUrl = Capsule::table('tblconfiguration')
            ->where('setting', 'SystemURL')
            ->value('value');

        $this->baseUrl = rtrim((string) $sysUrl, '/');
    }

    /**
     * Gera a URL de download do DANFS-e para uma NFS-e.
     */
    public function danfseUrl(Nfse $nfse): string
    {
        return $this->build($nfse->id, 'danfse');
    }

    /**
     * Gera a URL de download do XML para uma NFS-e.
     */
    public function xmlUrl(Nfse $nfse): string
    {
        return $this->build($nfse->id, 'xml');
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function build(int $nfseId, string $type): string
    {
        $token = TokenSigner::sign($nfseId . ':' . $type);

        return $this->baseUrl
            . '/index.php?m=nfsenacional&dl=' . $type
            . '&id=' . $nfseId
            . '&token=' . urlencode($token);
    }
}
