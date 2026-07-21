<?php

namespace GK2\NfseNacional\Transport\Auth;

use GK2\NfseNacional\Config\ModuleConfig;

/**
 * Autenticacao via Bearer token.
 *
 * Estrategia alternativa para APIs que aceitam token ao inves
 * de certificado digital. Preparada para uso futuro caso a
 * API Nacional disponibilize esse mecanismo.
 */
class TokenAuth implements AuthStrategyInterface
{
    private ModuleConfig $config;

    public function __construct(?ModuleConfig $config = null)
    {
        $this->config = $config ?? new ModuleConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array &$guzzleOptions): void
    {
        $token = $this->config->get('api_token');

        if (!empty($token)) {
            $guzzleOptions['headers']['Authorization'] = 'Bearer ' . $token;
        }
    }
}
