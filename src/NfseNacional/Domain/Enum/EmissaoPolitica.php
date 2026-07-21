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
    /**
     * Determina se deve emitir com base no hook que disparou a emissão.
     *
     * FATURA_GERADA só responde ao InvoiceCreated.
     * FATURA_PAGA   só responde ao InvoicePaid.
     *
     * Isso evita que "Fatura Gerada" dispare novamente ao pagar uma fatura
     * que não foi emitida na criação (ex: módulo ativado depois).
     */
    public function deveEmitir(string $hookNome): bool
    {
        return match ($this) {
            self::NAO_EMITIR    => false,
            self::FATURA_GERADA => $hookNome === 'InvoiceCreated',
            self::FATURA_PAGA   => $hookNome === 'InvoicePaid',
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
