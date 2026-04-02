<?php

namespace GK2\NfseNacional\Domain\Enum;

/**
 * Politica de emissao automatica de NFS-e.
 */
enum EmissaoPolitica: int
{
    case NAO_EMITIR = 1;
    case FATURA_GERADA = 2;
    case FATURA_PAGA = 3;

    /**
     * Determina se deve emitir com base no status atual da fatura.
     */
    public function deveEmitir(string $invoiceStatus): bool
    {
        return match ($this) {
            self::NAO_EMITIR => false,
            self::FATURA_GERADA => in_array($invoiceStatus, ['Unpaid', 'Paid', 'Collections', 'Overdue']),
            self::FATURA_PAGA => $invoiceStatus === 'Paid',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::NAO_EMITIR => 'Nao Emitir',
            self::FATURA_GERADA => 'Fatura Gerada',
            self::FATURA_PAGA => 'Fatura Paga',
        };
    }
}
