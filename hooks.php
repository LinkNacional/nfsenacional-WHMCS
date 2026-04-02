<?php
/**
 * NFS-e Nacional - Registro de Hooks WHMCS
 *
 * Arquivo carregado automaticamente pelo WHMCS para registrar
 * todos os hooks do addon.
 */

if (!defined('WHMCS')) {
    exit("This file cannot be accessed directly");
}

require_once __DIR__ . '/src/NfseNacional/Bootstrap.php';

use GK2\NfseNacional\Bootstrap;
use GK2\NfseNacional\Hook\HookHandler;

Bootstrap::boot();
HookHandler::register();
