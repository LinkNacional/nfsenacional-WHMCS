<?php

namespace GK2\NfseNacional\Persistence;

use GK2\NfseNacional\Domain\AmbienteGuard;
use GK2\NfseNacional\Domain\Entity\Nfse;
use GK2\NfseNacional\Domain\Enum\NfseStatus;
use WHMCS\Database\Capsule;

/**
 * Repositorio para operacoes CRUD na tabela tblnfsenacional.
 *
 * Centraliza todos os acessos ao banco de dados relacionados
 * a registros de NFS-e Nacional.
 *
 * REGRA CRITICA: todas as queries que retornam notas filtram
 * pelo ambiente ativo (via AmbienteGuard), impedindo que notas
 * de producao sejam visíveis/operaveis em homologacao e vice-versa.
 */
class NfseRepository
{
    private const TABLE = 'tblnfsenacional';

    private AmbienteGuard $guard;

    public function __construct(?AmbienteGuard $guard = null)
    {
        $this->guard = $guard ?? AmbienteGuard::getInstance();
    }

    /**
     * Busca NFS-e por ID da fatura (retorna a mais recente DO AMBIENTE ATIVO).
     */
    public function findByInvoice(int $invoiceId): ?Nfse
    {
        $row = Capsule::table(self::TABLE)
            ->where('id_invoice', $invoiceId)
            ->where('ambiente', $this->guard->value())
            ->orderBy('id', 'desc')
            ->first();

        return $row ? Nfse::fromRow($row) : null;
    }

    /**
     * Busca NFS-e por chave de acesso (valida ambiente).
     */
    public function findByChaveAcesso(string $chaveAcesso): ?Nfse
    {
        $row = Capsule::table(self::TABLE)
            ->where('chave_acesso', $chaveAcesso)
            ->where('ambiente', $this->guard->value())
            ->first();

        return $row ? Nfse::fromRow($row) : null;
    }

    /**
     * Busca NFS-e por ID interno (valida ambiente apos busca).
     */
    public function findById(int $id): ?Nfse
    {
        $row = Capsule::table(self::TABLE)->where('id', $id)->first();
        if ($row === null) {
            return null;
        }

        $nfse = Nfse::fromRow($row);
        // Cross-check: bloqueia se a nota pertence a outro ambiente
        $nfse->assertAmbiente($this->guard);

        return $nfse;
    }

    /**
     * Retorna todas as NFS-e com status PROCESSANDO DO AMBIENTE ATIVO.
     *
     * @return array Array de stdClass (registros brutos)
     */
    public function findPendentes(): array
    {
        return Capsule::table(self::TABLE)
            ->where('status', NfseStatus::PROCESSANDO->value)
            ->where('ambiente', $this->guard->value())
            ->orderBy('id', 'asc')
            ->get()
            ->all();
    }

    /**
     * Retorna NFS-e de um cliente com paginacao (somente ambiente ativo).
     *
     * @return array Array de Nfse entities
     */
    public function findByClient(int $clientId, int $limit = 50, int $offset = 0): array
    {
        $rows = Capsule::table(self::TABLE)
            ->where('id_client', $clientId)
            ->where('ambiente', $this->guard->value())
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return array_map(fn($row) => Nfse::fromRow($row), $rows->all());
    }

    /**
     * Conta NFS-e de um cliente (somente ambiente ativo).
     */
    public function countByClient(int $clientId): int
    {
        return Capsule::table(self::TABLE)
            ->where('id_client', $clientId)
            ->where('ambiente', $this->guard->value())
            ->count();
    }

    /**
     * Cria ou atualiza registro de NFS-e para uma fatura.
     *
     * @param int $invoiceId ID da fatura
     * @param array $fields Campos a inserir/atualizar
     * @return int ID do registro
     */
    public function createOrUpdate(int $invoiceId, array $fields): int
    {
        $fields['updated_at'] = date('Y-m-d H:i:s');
        $fields['id_invoice'] = $invoiceId;

        $existing = Capsule::table(self::TABLE)
            ->where('id_invoice', $invoiceId)
            ->first();

        if ($existing) {
            Capsule::table(self::TABLE)
                ->where('id', $existing->id)
                ->update($fields);

            return $existing->id;
        }

        $fields['created_at'] = date('Y-m-d H:i:s');
        return Capsule::table(self::TABLE)->insertGetId($fields);
    }

    /**
     * Atualiza o status de uma NFS-e.
     *
     * @param int $id ID do registro
     * @param NfseStatus $status Novo status
     * @param array $extra Campos adicionais a atualizar
     */
    public function updateStatus(int $id, NfseStatus $status, array $extra = []): bool
    {
        $fields = array_merge($extra, [
            'status' => $status->value,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return Capsule::table(self::TABLE)
            ->where('id', $id)
            ->update($fields) > 0;
    }

    /**
     * Retorna estatisticas do mes atual (somente ambiente ativo).
     */
    public function stats(?string $yearMonth = null): array
    {
        $yearMonth = $yearMonth ?? date('Y-m');

        $query = Capsule::table(self::TABLE)
            ->where('ambiente', $this->guard->value())
            ->where('updated_at', 'like', $yearMonth . '%');

        return [
            'autorizadas' => (clone $query)->where('status', NfseStatus::AUTORIZADA->value)->count(),
            'processando' => (clone $query)->where('status', NfseStatus::PROCESSANDO->value)->count(),
            'erros' => (clone $query)->where('status', NfseStatus::ERRO->value)->count(),
            'canceladas' => (clone $query)->where('status', NfseStatus::CANCELADA->value)->count(),
        ];
    }

    /**
     * Pesquisa NFS-e com filtros.
     */
    public function search(
        array $filters = [],
        string $orderBy = 'id',
        string $direction = 'desc',
        int $limit = 25,
        int $offset = 0,
    ): array {
        $query = Capsule::table(self::TABLE);

        // SEMPRE filtrar pelo ambiente ativo — nunca exibir notas de outro ambiente
        $query->where('ambiente', $this->guard->value());

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        // Nota: filtro 'ambiente' dos $filters e ignorado — sempre usa o ambiente ativo
        if (!empty($filters['id_invoice'])) {
            $query->where('id_invoice', $filters['id_invoice']);
        }
        if (!empty($filters['id_client'])) {
            $query->where('id_client', $filters['id_client']);
        }
        if (!empty($filters['chave_acesso'])) {
            $query->where('chave_acesso', 'like', '%' . $filters['chave_acesso'] . '%');
        }
        if (!empty($filters['client_name'])) {
            $query->where('client_name', 'like', '%' . $filters['client_name'] . '%');
        }
        if (!empty($filters['data_inicio'])) {
            $query->where('created_at', '>=', $filters['data_inicio']);
        }
        if (!empty($filters['data_fim'])) {
            $query->where('created_at', '<=', $filters['data_fim'] . ' 23:59:59');
        }

        $total = (clone $query)->count();

        $rows = $query
            ->orderBy($orderBy, $direction)
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'data' => array_map(fn($row) => Nfse::fromRow($row), $rows->all()),
        ];
    }

    /**
     * Exclui um registro por ID.
     */
    public function delete(int $id): bool
    {
        return Capsule::table(self::TABLE)->where('id', $id)->delete() > 0;
    }
}
