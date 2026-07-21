<?php

namespace GK2\NfseNacional\Transport\Auth;

/**
 * Contrato para estrategias de autenticacao na API NFS-e Nacional.
 *
 * Permite alternar entre mTLS (certificado digital) e token (Bearer)
 * sem modificar o HttpClient.
 */
interface AuthStrategyInterface
{
    /**
     * Configura as opcoes de autenticacao no array de opcoes do Guzzle.
     *
     * @param array $guzzleOptions Opcoes do Guzzle (passadas por referencia)
     */
    public function configure(array &$guzzleOptions): void;
}
