<?php

namespace GK2\NfseNacional\Fiscal;

/**
 * Registro de provedores fiscais disponiveis.
 *
 * Mapeia chaves de configuracao (ex: 'sefin', 'notacontrol') para
 * classes que implementam ProviderInterface.
 */
class ProviderRegistry
{
    /** @var array<string, class-string<ProviderInterface>> */
    private array $providers = [];

    /**
     * Registra um provedor.
     *
     * @param string $key   Chave usada na configuracao (ex: 'sefin')
     * @param class-string<ProviderInterface> $class FQCN da classe
     */
    public function register(string $key, string $class): void
    {
        if (!is_subclass_of($class, ProviderInterface::class)) {
            throw new \InvalidArgumentException(
                "Provider '{$class}' deve implementar ProviderInterface.",
            );
        }

        $this->providers[$key] = $class;
    }

    /**
     * Verifica se uma chave de provedor esta registrada.
     */
    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    /**
     * Retorna todas as chaves registradas.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Retorna a FQCN da classe para uma chave.
     *
     * @throws \InvalidArgumentException se a chave nao estiver registrada
     * @return class-string<ProviderInterface>
     */
    public function classFor(string $key): string
    {
        if (!isset($this->providers[$key])) {
            $available = implode(', ', $this->keys());
            throw new \InvalidArgumentException(
                "Provedor '{$key}' nao registrado. Disponiveis: [{$available}]",
            );
        }

        return $this->providers[$key];
    }
}
