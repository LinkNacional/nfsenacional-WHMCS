<?php

namespace GK2\NfseNacional\ClientArea;

use GK2\NfseNacional\Domain\Service\DownloadUrlService;
use GK2\NfseNacional\Domain\Service\EmailService;
use GK2\NfseNacional\Persistence\NfseRepository;
use GK2\NfseNacional\Security\TokenSigner;

/**
 * Controller da area do cliente para o modulo NFS-e Nacional.
 *
 * Renderiza a listagem de NFS-e do cliente logado.
 * Suporta modo AJAX: quando $_GET['ajax'] = '1', retorna JSON e encerra.
 */
class ClientAreaController
{
    private const SORT_MAP = [
        'numero' => 'numero_nfse_nacional',
        'fatura' => 'id_invoice',
        'data'   => 'data_autorizacao',
        'valor'  => 'total',
        'status' => 'status',
    ];

    private NfseRepository $repository;

    public function __construct()
    {
        $this->repository = new NfseRepository();
    }

    /**
     * Responde requisicoes AJAX da tabela de NFS-e.
     * Retorna JSON e encerra a execucao.
     */
    public function handleAjax(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $clientId = (int) ($_SESSION['uid'] ?? 0);
        if ($clientId <= 0) {
            echo json_encode(['error' => 'Não autenticado.']);
            exit;
        }

        $action = $_GET['action'] ?? '';

        // Acao de reenvio de email via AJAX
        if ($action === 'reenviar') {
            $resultado = $this->handleReenviar($clientId);
            echo json_encode([
                'sucesso' => $resultado['sucesso'],
                'msg'     => $resultado['msg'],
            ]);
            exit;
        }

        // Busca de dados da tabela
        $limit    = 10;
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $search   = trim($_GET['search'] ?? '');
        $offset   = ($page - 1) * $limit;
        $sortKey  = array_key_exists($_GET['orderby'] ?? '', self::SORT_MAP)
                        ? $_GET['orderby'] : 'data';
        $dir      = (($_GET['dir'] ?? 'desc') === 'asc') ? 'asc' : 'desc';
        $orderCol = self::SORT_MAP[$sortKey];

        $totalCount = $this->repository->countByClient($clientId, $search);
        $notas      = $this->repository->findByClient($clientId, $limit, $offset, $search, $orderCol, $dir);
        $totalPages = (int) ceil($totalCount / $limit);

        $urlService = new DownloadUrlService();
        $notasData  = [];
        foreach ($notas as $nfse) {
            $autorizada  = $nfse->isAutorizada();
            $notasData[] = [
                'id'               => $nfse->id,
                'invoice_id'       => $nfse->invoiceId,
                'numero'           => $nfse->numeroNfseNacional ?? '',
                'status'           => $nfse->status->label(),
                'status_raw'       => $nfse->status->value,
                'total'            => number_format($nfse->total ?? 0, 2, ',', '.'),
                'data_autorizacao' => $nfse->dataAutorizacao ?? '',
                'danfse_url'       => $autorizada ? $urlService->danfseUrl($nfse) : '',
                'xml_url'          => $autorizada ? $urlService->xmlUrl($nfse) : '',
                'pode_reenviar'    => $autorizada,
                'reenviar_token'   => $autorizada
                                        ? TokenSigner::sign($nfse->invoiceId . ':' . $clientId)
                                        : '',
                'created_at'       => $nfse->createdAt ?? '',
            ];
        }

        echo json_encode([
            'total'       => $totalCount,
            'total_pages' => $totalPages,
            'page'        => $page,
            'orderby'     => $sortKey,
            'dir'         => $dir,
            'search'      => $search,
            'notas'       => $notasData,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Renderiza a pagina da area do cliente (retorna array para o WHMCS).
     *
     * @param array $vars Variaveis do WHMCS (clientarea)
     * @return array Retorno padrao do WHMCS clientarea
     */
    public function render(array $vars): array
    {
        $clientId   = (int) ($_SESSION['uid'] ?? 0);
        $modulelink = $vars['modulelink'] ?? 'index.php?m=nfsenacional';

        // Estado inicial para bootstrap do JS (evita flash de conteúdo vazio)
        $initialOrderby = array_key_exists($_GET['orderby'] ?? '', self::SORT_MAP)
                            ? $_GET['orderby'] : 'data';
        $initialDir     = (($_GET['dir'] ?? 'desc') === 'asc') ? 'asc' : 'desc';
        $initialSearch  = trim($_GET['search'] ?? '');
        $initialPage    = max(1, (int) ($_GET['page'] ?? 1));

        return [
            'pagetitle'    => 'NFS-e Nacional',
            'tagline'      => 'Notas Fiscais Eletrônicas',
            'breadcrumb'   => [
                'clientarea.php'           => 'Área do Cliente',
                'index.php?m=nfsenacional' => 'NFS-e Nacional',
            ],
            'templatefile' => 'templates/client/home',
            'vars'         => [
                'modulelink'      => $modulelink,
                'initial_orderby' => $initialOrderby,
                'initial_dir'     => $initialDir,
                'initial_search'  => $initialSearch,
                'initial_page'    => $initialPage,
            ],
            'forcessl'     => true,
            'requirelogin' => true,
        ];
    }

    /**
     * Trata reenvio de email solicitado pelo cliente.
     * Retorna array ['sucesso' => bool, 'msg' => string].
     */
    private function handleReenviar(int $clientId): array
    {
        $invoiceId = (int) ($_GET['invoiceid'] ?? 0);
        $token     = $_GET['token'] ?? '';

        if ($invoiceId <= 0 || !TokenSigner::verify($invoiceId . ':' . $clientId, $token)) {
            return ['sucesso' => false, 'msg' => 'Token inválido.'];
        }

        $nfse = $this->repository->findByInvoice($invoiceId);
        if ($nfse === null || (int) $nfse->clientId !== $clientId) {
            return ['sucesso' => false, 'msg' => 'NFS-e não encontrada.'];
        }

        return (new EmailService())->enviar($invoiceId);
    }
}
