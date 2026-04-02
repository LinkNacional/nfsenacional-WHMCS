<?php

namespace GK2\NfseNacional\Domain\Enum;

/**
 * Ambiente de operacao da API NFS-e Nacional.
 */
enum Ambiente: string
{
    case HOMOLOGACAO = 'homologacao';
    case PRODUCAO = 'producao';

    public function isProducao(): bool
    {
        return $this === self::PRODUCAO;
    }

    public function label(): string
    {
        return match ($this) {
            self::HOMOLOGACAO => 'Homologacao',
            self::PRODUCAO => 'Producao',
        };
    }
}
