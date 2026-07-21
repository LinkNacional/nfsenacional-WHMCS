<?php

namespace GK2\NfseNacional;

/**
 * Inicializacao do modulo NFS-e Nacional.
 *
 * Registra o autoloader PSR-4 (via Composer ou fallback manual)
 * e prepara o ambiente para uso das classes do modulo.
 */
class Bootstrap
{
    private static bool $booted = false;

    /**
     * Inicializa o modulo.
     * Seguro para chamar multiplas vezes (idempotente).
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::registerAutoloader();
        self::$booted = true;
    }

    /**
     * Registra o autoloader.
     * Tenta usar o Composer primeiro; se nao disponivel, usa spl_autoload_register.
     */
    private static function registerAutoloader(): void
    {
        $composerAutoload = __DIR__ . '/../../vendor/autoload.php';

        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
            return;
        }

        // Fallback: autoloader manual PSR-4
        spl_autoload_register(function (string $class): void {
            $prefix = 'GK2\\NfseNacional\\';
            $baseDir = __DIR__ . '/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });
    }
}
