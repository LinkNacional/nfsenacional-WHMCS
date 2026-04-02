<?php
/**
 * NFS-e Nacional - Cron Job
 *
 * Processa DPS pendentes (status PROCESSANDO) consultando a API Nacional
 * para verificar se ja foram autorizadas.
 *
 * Uso: php -q modules/addons/nfsenacional/cron.php
 */

// Inicializar WHMCS
$whmcsDir = realpath(__DIR__ . '/../../../');
require_once $whmcsDir . '/init.php';

require_once __DIR__ . '/src/NfseNacional/Bootstrap.php';

use GK2\NfseNacional\Bootstrap;
use GK2\NfseNacional\Domain\Service\ConsultaService;

use GK2\NfseNacional\Domain\AmbienteGuard;

Bootstrap::boot();

try {
    $guard = AmbienteGuard::getInstance();
    $ambiente = $guard->getAmbiente();

    if (php_sapi_name() === 'cli') {
        echo "NFS-e Nacional: ambiente ativo = {$ambiente->value}\n";
    }

    $service = new ConsultaService(null, $guard);
    $processados = $service->processarPendentes();

    if (php_sapi_name() === 'cli') {
        echo "NFS-e Nacional [{$ambiente->value}]: {$processados} documento(s) processado(s).\n";
    }
} catch (\Exception $e) {
    logModuleCall(
        'nfsenacional',
        'Cron-Error',
        [],
        $e->getMessage(),
        $e->getTraceAsString()
    );

    if (php_sapi_name() === 'cli') {
        echo "Erro: {$e->getMessage()}\n";
        exit(1);
    }
}
