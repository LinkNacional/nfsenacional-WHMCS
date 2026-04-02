<?php

namespace GK2\NfseNacional\Persistence;

use GK2\NfseNacional\Domain\Enum\Ambiente;
use WHMCS\Database\Capsule;

/**
 * Gerencia a numeracao sequencial de DPS (Declaracao de Prestacao de Servicos).
 *
 * Armazena o proximo numero disponivel em tbladdonmodules,
 * com chaves por serie e ambiente (ex: dps_proximo_homologacao_DPS).
 *
 * Segue o mesmo padrao do modulo notafiscal para numeracao de RPS.
 */
class DpsSequence
{
    private const MODULE_NAME = 'nfsenacional';

    /**
     * Retorna o proximo numero de DPS e incrementa o contador.
     *
     * @param string $serie Serie da DPS
     * @param Ambiente $ambiente Ambiente (homologacao/producao)
     * @return int Proximo numero disponivel
     */
    public function next(string $serie, Ambiente $ambiente): int
    {
        $key = $this->getSettingKey($serie, $ambiente);
        $current = $this->current($serie, $ambiente);
        $next = max($current, 1);

        // Salvar proximo valor
        $this->setSetting($key, (string) ($next + 1));

        return $next;
    }

    /**
     * Retorna o numero atual sem incrementar.
     */
    public function current(string $serie, Ambiente $ambiente): int
    {
        $key = $this->getSettingKey($serie, $ambiente);
        $value = $this->getSetting($key);

        if (empty($value)) {
            // Fallback: usar configuracao geral dps_proximo
            $value = $this->getSetting('dps_proximo');
        }

        return (int) $value;
    }

    /**
     * Define um valor especifico para o contador.
     */
    public function reset(string $serie, Ambiente $ambiente, int $value): void
    {
        $key = $this->getSettingKey($serie, $ambiente);
        $this->setSetting($key, (string) $value);
    }

    /**
     * Gera a chave de configuracao para a combinacao serie+ambiente.
     */
    private function getSettingKey(string $serie, Ambiente $ambiente): string
    {
        return 'dps_proximo_' . $ambiente->value . '_' . $serie;
    }

    /**
     * Le uma configuracao do addon.
     */
    private function getSetting(string $key): string
    {
        return (string) Capsule::table('tbladdonmodules')
            ->where('module', self::MODULE_NAME)
            ->where('setting', $key)
            ->value('value');
    }

    /**
     * Salva uma configuracao do addon (insert ou update).
     */
    private function setSetting(string $key, string $value): void
    {
        $exists = Capsule::table('tbladdonmodules')
            ->where('module', self::MODULE_NAME)
            ->where('setting', $key)
            ->exists();

        if ($exists) {
            Capsule::table('tbladdonmodules')
                ->where('module', self::MODULE_NAME)
                ->where('setting', $key)
                ->update(['value' => $value]);
        } else {
            Capsule::table('tbladdonmodules')->insert([
                'module' => self::MODULE_NAME,
                'setting' => $key,
                'value' => $value,
            ]);
        }
    }
}
