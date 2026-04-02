<?php

namespace GK2\NfseNacional\Hook;

use GK2\NfseNacional\Persistence\NfseRepository;

/**
 * Registra itens de menu na area do cliente para NFS-e Nacional.
 *
 * Adiciona link "NFS-e Nacional" na secao Billing do menu principal
 * e reconstroi a sidebar completa quando na pagina do modulo,
 * incluindo itens padrao (Faturas, Orcamentos, Add Funds).
 */
class ClientAreaMenu
{
    /**
     * Registra os hooks de menu do client area.
     */
    public function register(): void
    {
        // Menu principal
        add_hook('ClientAreaPrimaryNavbar', -1, function ($navbar) {
            $this->addPrimaryNavItem($navbar);
        });

        // Sidebar — sempre ativa na pagina do modulo
        add_hook('ClientAreaSecondarySidebar', -1, function ($sidebar) {
            $this->addSidebarItem($sidebar);
        });

        // Sidebar — garantir sidebar na pagina do modulo mesmo sem notas
        add_hook('ClientAreaSecondarySidebar', -1, function ($sidebar) {
            $this->ensureSidebarOnModulePage($sidebar);
        });
    }

    /**
     * Adiciona item no menu principal (Billing).
     */
    private function addPrimaryNavItem($navbar): void
    {
        $client = $this->getCurrentClient();
        if ($client === null) {
            return;
        }

        if (!$this->clientHasNfse($client)) {
            return;
        }

        $billingMenu = $navbar->getChild('Billing');
        if ($billingMenu !== null) {
            $billingMenu->addChild('NFS-e Nacional', [
                'label' => 'Notas Fiscais (Nacional)',
                'uri' => 'index.php?m=nfsenacional',
                'order' => 11,
            ]);
        }
    }

    /**
     * Adiciona item na sidebar quando cliente tem notas.
     */
    private function addSidebarItem($sidebar): void
    {
        if (!isset($_REQUEST['m']) || $_REQUEST['m'] !== 'nfsenacional') {
            return;
        }

        $client = $this->getCurrentClient();
        if ($client === null) {
            return;
        }

        $this->buildFullBillingSidebar($sidebar);
    }

    /**
     * Garante que a sidebar Billing exista na pagina do modulo,
     * mesmo para clientes sem notas (fallback).
     */
    private function ensureSidebarOnModulePage($sidebar): void
    {
        if (!isset($_REQUEST['m']) || $_REQUEST['m'] !== 'nfsenacional') {
            return;
        }

        $this->buildFullBillingSidebar($sidebar);
    }

    /**
     * Reconstroi a sidebar Billing completa com todos os itens padrao
     * e o item NFS-e Nacional ativo.
     *
     * Replica o comportamento do modulo notafiscal (menu.php).
     */
    private function buildFullBillingSidebar($sidebar): void
    {
        $billing = $sidebar->getChild('Billing');
        if ($billing === null) {
            $billing = $sidebar->addChild('Billing', [
                'label' => 'Financeiro',
                'order' => 20,
                'icon' => 'fas fa-university',
            ]);
        }

        if ($billing === null) {
            return;
        }

        // Itens padrao do Financeiro
        if ($billing->getChild('Invoices') === null) {
            $billing->addChild('Invoices', [
                'label' => 'Minhas Faturas',
                'uri' => 'clientarea.php?action=invoices',
                'order' => 10,
                'icon' => 'fas fa-ticket ls ls-document',
            ]);
        }

        if ($billing->getChild('Quotes') === null) {
            $billing->addChild('Quotes', [
                'label' => 'Meus Orcamentos',
                'uri' => 'clientarea.php?action=quotes',
                'order' => 30,
                'icon' => 'fas fa-ticket ls ls-text-cloud',
            ]);
        }

        if ($billing->getChild('Add Funds') === null) {
            $billing->addChild('Add Funds', [
                'label' => 'Adicionar Saldo',
                'uri' => 'clientarea.php?action=addfunds',
                'order' => 40,
                'icon' => 'fas fa-ticket ls ls-credit',
            ]);
        }

        // Item NFS-e Nacional (ativo)
        $isActive = isset($_REQUEST['m']) && $_REQUEST['m'] === 'nfsenacional';
        if ($billing->getChild('NFS-e Nacional') === null) {
            $billing->addChild('NFS-e Nacional', [
                'label' => 'Notas Fiscais (Nacional)',
                'uri' => 'index.php?m=nfsenacional',
                'order' => 20,
                'icon' => 'fas fa-receipt',
                'class' => $isActive ? 'active' : '',
            ]);
        }
    }

    /**
     * Retorna o ID do cliente logado, se houver.
     */
    private function getCurrentClient(): ?int
    {
        $session = \WHMCS\Session::get('uid');
        return !empty($session) ? (int) $session : null;
    }

    /**
     * Verifica se o cliente possui NFS-e emitidas.
     */
    private function clientHasNfse(int $clientId): bool
    {
        $repository = new NfseRepository();
        return $repository->countByClient($clientId) > 0;
    }
}
