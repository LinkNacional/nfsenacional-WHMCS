<?php

namespace GK2\NfseNacional\Config;

use GK2\NfseNacional\Domain\Enum\Ambiente;
use GK2\NfseNacional\Domain\Enum\EmissaoPolitica;
use WHMCS\Database\Capsule;

/**
 * Gerencia configuracoes do addon NFS-e Nacional.
 *
 * Le e escreve settings em tbladdonmodules WHERE module = 'nfsenacional'.
 * Fornece getters tipados para cada configuracao.
 */
class ModuleConfig
{
    private const MODULE_NAME = 'nfsenacional';

    private ?array $cache = null;

    /**
     * Retorna todas as configuracoes do addon como array associativo.
     */
    public function getAll(): array
    {
        if ($this->cache === null) {
            $this->cache = [];
            $rows = Capsule::table('tbladdonmodules')
                ->where('module', self::MODULE_NAME)
                ->get(['setting', 'value']);

            foreach ($rows as $row) {
                $this->cache[$row->setting] = $row->value;
            }
        }

        return $this->cache;
    }

    /**
     * Retorna o valor de uma configuracao especifica.
     */
    public function get(string $key, string $default = ''): string
    {
        $all = $this->getAll();
        return $all[$key] ?? $default;
    }

    /**
     * Atualiza ou insere uma configuracao.
     */
    public function set(string $key, string $value): void
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

        // Invalida cache
        $this->cache = null;
    }

    // ─── Getters tipados ───────────────────────────────────────────

    public function getAmbiente(): Ambiente
    {
        $valor = $this->get('ambiente', 'homologacao');
        return Ambiente::from($valor);
    }

    public function getCnpjPrestador(): string
    {
        return preg_replace('/\D/', '', $this->get('cnpj_prestador'));
    }

    public function getInscricaoMunicipal(): string
    {
        return $this->get('inscricao_municipal');
    }

    public function getRegApTribSN(): string
    {
        $valor = $this->get('reg_ap_trib_sn', '1');
        return (string) ((int) preg_replace('/\D.*/', '', $valor) ?: 1);
    }

    public function getCodigoMunicipioPrestador(): string
    {
        return $this->get('codigo_municipio_prestador', '');
    }

    public function getCertificadoPath(): string
    {
        return $this->get('certificado_path');
    }

    public function getCertificadoSenha(): string
    {
        return $this->get('certificado_senha');
    }

    public function getCnae(): string
    {
        return $this->get('cnae', '');
    }

    public function getCodigoServico(): string
    {
        return $this->get('codigoservico', '');
    }

    public function getCodigoServicoNacional(): string
    {
        return $this->get('codigo_servico_nacional');
    }

    public function getCodigoMunicipal(): string
    {
        return $this->get('codigomunicipal', '');
    }

    public function getExigibilidadeIss(): int
    {
        $valor = $this->get('exigibilidade_iss', '1');
        return (int) preg_replace('/\D.*/', '', $valor) ?: 1;
    }

    public function getRegEspTrib(): string
    {
        $valor = $this->get('reg_esp_trib', '0');
        return (string) ((int) preg_replace('/\D.*/', '', $valor));
    }

    public function getAliquotaIss(): float
    {
        return (float) $this->get('iss', '0');
    }

    public function getRetencaoIss(): float
    {
        return (float) $this->get('reteriss', '0');
    }

    public function isReterIssFatura(): bool
    {
        return $this->get('reterissfatura') === 'on';
    }

    public function isOptanteSimplesNacional(): bool
    {
        return $this->get('optante_simples') === 'on';
    }

    public function getSerieDps(): string
    {
        return $this->get('serie_dps', '1');
    }

    public function getEmissaoPadrao(): EmissaoPolitica
    {
        $valor = $this->get('emissao_padrao', '1');
        $numero = (int) preg_replace('/\D.*/', '', $valor);
        return EmissaoPolitica::tryFrom($numero) ?? EmissaoPolitica::NAO_EMITIR;
    }

    public function isEmailHabilitado(): bool
    {
        return $this->get('email') === 'on';
    }

    public function isCancelarComFatura(): bool
    {
        return $this->get('cancelar') === 'on';
    }

    public function isConsiderarDescontos(): bool
    {
        return $this->get('desconto') === 'on';
    }

    public function isExcluirAddFunds(): bool
    {
        return $this->get('addfunds') === 'on';
    }

    public function isExcluirLateFee(): bool
    {
        return $this->get('excluir_latefee') === 'on';
    }

    public function isDebug(): bool
    {
        return $this->get('debug') === 'on';
    }

    public function getDocumentoCliente(): string
    {
        return $this->get('documento_cliente', 'taxid');
    }

    public function isProdutosPersonalizados(): bool
    {
        return $this->get('produtos') === 'on';
    }

    // ─── Setup (ativacao) ──────────────────────────────────────────

    /**
     * Insere configuracoes padrao na ativacao do modulo.
     */
    public function insertDefaults(): void
    {
        $defaults = [
            'ambiente' => 'homologacao',
            'serie_dps' => '1',
            'dps_proximo' => '',
            'excluir_latefee' => '0',
            'documento_cliente' => 'taxid',
            'optante_simples' => '1',
            'emissao_padrao' => '1-Nao Emitir',
            'email' => '0',
            'cancelar' => '0',
            'desconto' => '0',
            'addfunds' => '0',
            'debug' => '0',
        ];

        foreach ($defaults as $setting => $value) {
            $exists = Capsule::table('tbladdonmodules')
                ->where('module', self::MODULE_NAME)
                ->where('setting', $setting)
                ->exists();

            if (!$exists) {
                Capsule::table('tbladdonmodules')->insert([
                    'module' => self::MODULE_NAME,
                    'setting' => $setting,
                    'value' => $value,
                ]);
            }
        }
    }

    /**
     * Garante que o template de email "NFS-e Nacional" exista.
     */
    public function ensureEmailTemplate(): void
    {
        $tpl = Capsule::table('tblemailtemplates')
            ->where('name', 'NFS-e Nacional')
            ->first();

        if (empty($tpl)) {
            $message = '<p>Prezado {$client_name},</p>'
                . "\r\n"
                . '<p>Estamos enviando a nota fiscal eletronica de numero <strong>{$idNFS}</strong>, emitida em <strong>{$autorizacao}</strong>.</p>'
                . "\r\n"
                . '<p><a href="{$danfse_url}" target="_blank" rel="noopener"><strong>Ver DANFS-e</strong></a></p>'
                . "\r\n"
                . '<p><a href="{$xml_url}" target="_blank" rel="noopener"><strong>Ver XML</strong></a></p>'
                . "\r\n"
                . '<p>{$signature}</p>';

            Capsule::table('tblemailtemplates')->insert([
                'type' => 'invoice',
                'name' => 'NFS-e Nacional',
                'subject' => 'Nota Fiscal Eletronica - Fatura #{$idFatura}',
                'message' => $message,
                'plaintext' => 0,
                'custom' => 1,
            ]);
        }
    }

    /**
     * Garante que os custom fields do cliente existam.
     */
    public function ensureCustomFields(): void
    {
        // Campo: Emitir NFS-e (Nacional)
        $emitirId = Capsule::table('tblcustomfields')
            ->where('fieldname', 'Emitir NFS-e (Nacional)')
            ->value('id');

        if (empty($emitirId)) {
            Capsule::table('tblcustomfields')->insert([
                'type' => 'client',
                'fieldname' => 'Emitir NFS-e (Nacional)',
                'fieldtype' => 'dropdown',
                'adminonly' => 'on',
                'fieldoptions' => '1- Nao Emitir NFS-e,2- Fatura Gerada,3- Fatura Paga',
                'description' => '[Criado Automaticamente] NFS-e via Addon Nacional, nao altere as informacoes desse campo.',
            ]);
        }

        // Campo: Reter ISS (Nacional)
        $reterIssId = Capsule::table('tblcustomfields')
            ->where('fieldname', 'Reter ISS (Nacional)')
            ->value('id');

        if (empty($reterIssId)) {
            Capsule::table('tblcustomfields')->insert([
                'type' => 'client',
                'fieldname' => 'Reter ISS (Nacional)',
                'fieldtype' => 'tickbox',
                'adminonly' => 'on',
                'description' => '[Criado Automaticamente] NFS-e via Addon Nacional, nao altere as informacoes desse campo.',
            ]);
        }
    }
}
