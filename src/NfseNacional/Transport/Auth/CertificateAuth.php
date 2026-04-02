<?php

namespace GK2\NfseNacional\Transport\Auth;

use GK2\NfseNacional\Config\ModuleConfig;

/**
 * Autenticacao via certificado digital A1 (mTLS).
 *
 * Extrai chave privada e certificado do arquivo .pfx (PKCS#12)
 * e configura as opcoes de SSL do Guzzle para autenticacao mutua.
 */
class CertificateAuth implements AuthStrategyInterface
{
    private ModuleConfig $config;
    private ?string $certPemPath = null;
    private ?string $keyPemPath = null;

    public function __construct(?ModuleConfig $config = null)
    {
        $this->config = $config ?? new ModuleConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array &$guzzleOptions): void
    {
        $this->extractPem();

        if ($this->certPemPath && $this->keyPemPath) {
            $guzzleOptions['cert'] = $this->certPemPath;
            $guzzleOptions['ssl_key'] = $this->keyPemPath;
        }

        // Verificar SSL em producao, pode desabilitar em homologacao
        $guzzleOptions['verify'] = $this->config->getAmbiente()->isProducao();
    }

    /**
     * Extrai certificado e chave do arquivo PFX para arquivos PEM temporarios.
     */
    private function extractPem(): void
    {
        if ($this->certPemPath !== null) {
            return; // Ja extraido
        }

        $pfxPath = $this->config->getCertificadoPath();
        $senha = $this->config->getCertificadoSenha();

        if (empty($pfxPath) || !file_exists($pfxPath)) {
            throw new \RuntimeException('Certificado digital nao encontrado: ' . $pfxPath);
        }

        $pfxContent = file_get_contents($pfxPath);
        $certs = [];

        if (!openssl_pkcs12_read($pfxContent, $certs, $senha)) {
            throw new \RuntimeException('Erro ao ler certificado PFX. Verifique a senha.');
        }

        // Salvar em arquivos temporarios
        $tempDir = sys_get_temp_dir();
        $hash = md5($pfxPath . $senha);

        $this->certPemPath = $tempDir . '/nfsenacional_cert_' . $hash . '.pem';
        $this->keyPemPath = $tempDir . '/nfsenacional_key_' . $hash . '.pem';

        if (!file_exists($this->certPemPath)) {
            file_put_contents($this->certPemPath, $certs['cert']);
            chmod($this->certPemPath, 0600);
        }

        if (!file_exists($this->keyPemPath)) {
            file_put_contents($this->keyPemPath, $certs['pkey']);
            chmod($this->keyPemPath, 0600);
        }
    }

    /**
     * Remove arquivos temporarios (limpeza manual se necessario).
     */
    public function cleanup(): void
    {
        if ($this->certPemPath && file_exists($this->certPemPath)) {
            unlink($this->certPemPath);
        }
        if ($this->keyPemPath && file_exists($this->keyPemPath)) {
            unlink($this->keyPemPath);
        }

        $this->certPemPath = null;
        $this->keyPemPath = null;
    }
}
