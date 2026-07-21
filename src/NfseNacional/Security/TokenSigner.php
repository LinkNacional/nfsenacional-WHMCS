<?php

namespace GK2\NfseNacional\Security;

use GK2\NfseNacional\Config\ModuleConfig;

/**
 * Assina e verifica tokens HMAC para ações do módulo.
 *
 * Utiliza uma chave secreta única por instalação (gerada aleatoriamente
 * na ativação do addon e armazenada em tbladdonmodules).
 * Isso garante que tokens de uma instalação não sejam válidos em outra.
 *
 * Algoritmo: HMAC-SHA256
 * Comparação: hash_equals() (resistente a timing attacks)
 */
class TokenSigner
{
    /**
     * Gera o token HMAC para os dados fornecidos.
     *
     * @param string $data Dados a assinar (ex: invoiceId, nfseId, etc.)
     */
    public static function sign(string $data): string
    {
        $config = new ModuleConfig();
        return hash_hmac('sha256', $data, $config->getTokenSecret());
    }

    /**
     * Verifica se o token é válido para os dados fornecidos.
     * Usa hash_equals() para prevenir timing attacks.
     *
     * @param string $data  Dados originais
     * @param string $token Token recebido na requisição
     */
    public static function verify(string $data, string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        return hash_equals(self::sign($data), $token);
    }
}
