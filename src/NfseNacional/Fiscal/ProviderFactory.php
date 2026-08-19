<?php

namespace GK2\NfseNacional\Fiscal;

use GK2\NfseNacional\Config\ModuleConfig;
use GK2\NfseNacional\Domain\AmbienteGuard;
use GK2\NfseNacional\Fiscal\NotaControl\NotaControlProvider;

/**
 * Fabrica de provedores fiscais.
 *
 * Le a configuracao 'provedor' do ModuleConfig e instancia o provider
 * correspondente via ProviderRegistry. As dependencias comuns (config,
 * guard) sao injetadas automaticamente.
 */
class ProviderFactory
{
    private ProviderRegistry $registry;
    private ModuleConfig $config;
    private ?AmbienteGuard $guard;

    public function __construct(
        ?ProviderRegistry $registry = null,
        ?ModuleConfig $config = null,
        ?AmbienteGuard $guard = null,
    ) {
        $this->registry = $registry ?? self::defaultRegistry();
        $this->config = $config ?? new ModuleConfig();
        $this->guard = $guard;
    }

    /**
     * Cria o provider configurado no addon.
     *
     * Todos os providers devem aceitar (?ModuleConfig, ?AmbienteGuard)
     * como primeiros parametros do construtor.
     *
     * @throws \InvalidArgumentException se o provedor configurado nao existir
     */
    public function create(): ProviderInterface
    {
        $key = $this->config->getProvedor();
        $class = $this->registry->classFor($key);

        $guard = $this->guard ?? AmbienteGuard::getInstance($this->config);

        return new $class($this->config, $guard);
    }

    /**
     * Retorna o registro padrao com os providers nativos.
     */
    public static function defaultRegistry(): ProviderRegistry
    {
        $registry = new ProviderRegistry();
        $registry->register('sefin', NacionalProvider::class);
        $registry->register('notacontrol', NotaControlProvider::class);

        return $registry;
    }
}
