# NFS-e Nacional — Addon WHMCS

Addon para WHMCS que integra a emissão de faturas com a **NFS-e Nacional (ADN)** —
o padrão nacional de Nota Fiscal de Serviço Eletrônica mantido pela RFB/ENCAT
(https://www.gov.br/nfse). Cobre emissão, consulta, cancelamento, download de
DANFS-e/XML e envio automático por e-mail, com isolamento total entre os
ambientes de **produção** e **homologação**.

> Desenvolvido pela [GK2](https://gk2.com.br).

## Instalação

Guia completo de instalação e configuração (produção e homologação):
**https://oraculo.gk2.cloud/books/whmcs/page/configuracao-nfs-e-nacional-v10-producao-e-homologacao**

Resumo rápido:

1. Copie o conteúdo deste repositório para `modules/addons/nfsenacional/` na sua instalação WHMCS.
2. As dependências (Guzzle, xmlseclibs, PSR) já vêm versionadas em `vendor/` — não é necessário rodar `composer install` no servidor de produção.
3. Em **Configuration Value → System → Activate Modules → Other Addon Modules**, ative o addon "NFS-e Nacional".
4. Preencha as configurações do addon (certificado A1, série DPS, ambiente, política de emissão) — detalhado no guia acima.

## O que o addon faz

- Emite a DPS (Declaração de Prestação de Serviço) a partir de uma fatura paga/gerada no WHMCS e obtém a NFS-e autorizada pelo SEFIN Nacional.
- Consulta, cancela e reenvia por e-mail notas já emitidas.
- Disponibiliza download de DANFS-e (PDF) e XML tanto na área do cliente quanto no admin.
- Mantém histórico completo por fatura (status, protocolo, chave de acesso, valores de ISS) em tabela própria do banco.

## Arquitetura

O código vive em `src/NfseNacional/` sob o namespace `GK2\NfseNacional`, organizado
por responsabilidade (não por tipo de arquivo MVC):

```
nfsenacional.php     Entry point do addon (config/activate/deactivate/output/clientarea)
hooks.php             Registro dos hooks do WHMCS (carregado automaticamente)
src/NfseNacional/
├── Bootstrap.php              Autoload (Composer, com fallback PSR-4 manual)
├── Admin/                     Painel administrativo do addon
│   ├── AdminController.php    Dispatch de páginas (dashboard, list, detail)
│   ├── ConfigFields.php       Definição dos campos de configuração
│   └── Action/                Ações disparadas do admin (Emitir, Cancelar, Excluir, Reenviar e-mail)
├── ClientArea/                 Área do cliente (listagem de notas do usuário logado)
│   ├── ClientAreaController.php
│   └── DownloadController.php  Proxy de download (DANFS-e / XML)
├── Config/
│   └── ModuleConfig.php        Leitura/escrita das configurações do addon (inclui
│                                criptografia AES-256-CBC da senha do certificado)
├── Domain/                     Regras de negócio e modelos de domínio
│   ├── AmbienteGuard.php        Trava central: resolve o ambiente (produção/homologação)
│   │                             uma única vez por requisição e nunca deixa misturar dados
│   │                             ou chamadas de API entre os dois ambientes
│   ├── Entity/                  Dps, Nfse
│   ├── Enum/                    Ambiente, EmissaoPolitica, NfseStatus
│   └── Service/                 Orquestração dos casos de uso:
│       EmissaoService, ConsultaService, CancelamentoService,
│       EmailService, DownloadUrlService, CepIbgeCache
├── Fiscal/                     Tudo relacionado à montagem e assinatura do documento fiscal
│   ├── NacionalProvider.php     Implementação de ProviderInterface para a API ADN
│   ├── Mapper/                  Fatura/cliente WHMCS → estruturas fiscais
│   │                             (Prestador, Tomador, Serviço, Tributo)
│   ├── Payload/                 Monta o XML da DPS/Evento conforme XSD oficial
│   └── Signer/XmlSigner.php     Assinatura digital XML (via robrichards/xmlseclibs)
├── Hook/                        Integração com os hooks do WHMCS
│   ├── HookHandler.php          Registro central de todos os hooks
│   ├── InvoiceHooks.php         Gatilhos de emissão automática (fatura criada/paga)
│   └── AdminInvoiceUI.php, AdminInvoiceListUI.php,
│       ClientInvoiceListUI.php, ClientAreaMenu.php   Injeção de UI nas telas do WHMCS
├── Persistence/                 Acesso a dados (tblnfsenacional e tabelas de apoio)
│   ├── NfseRepository.php, Migration.php, DpsSequence.php
├── Security/TokenSigner.php     Assinatura/validação de tokens usados nos links de download
└── Transport/                   Cliente HTTP e autenticação com a API Nacional
    ├── HttpClient.php, ApiEndpoints.php, ApiResponse.php
    └── Auth/                    CertificateAuth (mTLS via certificado A1) e TokenAuth
```

### Fluxo de emissão (resumo)

1. Um hook (`InvoiceHooks`) ou uma ação manual do admin chama `EmissaoService::processarEmissao()`.
2. `AmbienteGuard` resolve o ambiente ativo (produção/homologação) uma vez e o propaga
   para repositório, provider e endpoints — impedindo qualquer mistura entre os dois.
3. `DpsPayloadBuilder` (usando os `Mapper`s) monta o XML da DPS a partir da fatura e do cliente.
4. `NacionalProvider` compacta o XML (GZip + base64) e envia para o SEFIN Nacional
   (`ApiEndpoints` resolve as URLs por ambiente: `*.nfse.gov.br` em produção,
   `*.producaorestrita.nfse.gov.br` em homologação).
5. A resposta é persistida via `NfseRepository`, e-mail é disparado se habilitado
   (`EmailService`), e o resultado é exibido no admin/área do cliente.

Consulta, cancelamento e obtenção de DANFS-e/XML seguem o mesmo padrão através de
`ConsultaService` e `CancelamentoService`.

## Requisitos

- PHP >= 8.1
- WHMCS >= 8.12
- Certificado digital A1 (.pfx) do prestador de serviços

## Stack

- [`guzzlehttp/guzzle`](https://github.com/guzzle/guzzle) — cliente HTTP (com autenticação mTLS via certificado A1)
- [`robrichards/xmlseclibs`](https://github.com/robrichards/xmlseclibs) — assinatura digital XML
