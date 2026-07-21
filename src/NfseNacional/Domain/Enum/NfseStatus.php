<?php

namespace GK2\NfseNacional\Domain\Enum;

/**
 * Status do ciclo de vida de uma NFS-e Nacional.
 */
enum NfseStatus: string
{
    case PENDENTE = 'PENDENTE';
    case PROCESSANDO = 'PROCESSANDO';
    case AUTORIZADA = 'AUTORIZADA';
    case CANCELADA = 'CANCELADA';
    case SUBSTITUIDA = 'SUBSTITUIDA';
    case ERRO = 'ERRO';

    /**
     * Indica se a nota esta em um estado final (nao pode mais mudar).
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::AUTORIZADA, self::CANCELADA, self::SUBSTITUIDA => true,
            default => false,
        };
    }

    /**
     * Indica se a nota pode ser cancelada.
     */
    public function podeCancelar(): bool
    {
        return $this === self::AUTORIZADA;
    }

    /**
     * Retorna label amigavel para exibicao.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::PROCESSANDO => 'Processando',
            self::AUTORIZADA => 'Autorizada',
            self::CANCELADA => 'Cancelada',
            self::SUBSTITUIDA => 'Substituida',
            self::ERRO => 'Erro',
        };
    }

    /**
     * Retorna classe CSS Bootstrap para badges de status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDENTE => 'label-default',
            self::PROCESSANDO => 'label-info',
            self::AUTORIZADA => 'label-success',
            self::CANCELADA => 'label-danger',
            self::SUBSTITUIDA => 'label-warning',
            self::ERRO => 'label-danger',
        };
    }
}
