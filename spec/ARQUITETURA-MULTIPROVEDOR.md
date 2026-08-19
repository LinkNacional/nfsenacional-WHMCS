# Plano: Arquitetura Multiprovedor — NFS-e Nacional WHMCS

> **Status:** Especificação | **Versão:** 1.0 | **Data:** 2026-08-07

## Objetivo

Transformar o módulo em um sistema **multiprovedor**, onde o usuário do WHMCS
seleciona qual provedor fiscal usar (Sefin Nacional, Nota Control, futuros) via
configuração do addon. O código fiscal (DPS, assinatura XML, eventos) é
compartilhado entre todos os provedores — apenas a camada de **transporte**
(protocolo HTTP, autenticação, envelope) muda.

---

## Diagnóstico: acoplamentos atuais

Três pontos onde o código está hardcoded para Sefin Nacional:

| # | Local | Problema |
| --- | --- | --- |
| 1 | `EmissaoService::$provider` | Type-hint concreto `NacionalProvider`, não a interface |
| 2 | `CancelamentoService::$provider` | Idem |
| 3 | `ConsultaService::$provider` | Idem |
| 4 | `EmissaoService::processarEmissao()` | Usa `ApiEndpoints` diretamente para gerar URLs de DANFSe/XML |
| 5 | `NacionalProvider` | Única implementação de `ProviderInterface` — não há factory |

**O que NÃO precisa mudar:**

- `DpsPayloadBuilder` — gera XML no namespace SPED, compatível com ambos
- `EventoPayloadBuilder` — idem
- `XmlSigner` — assinatura XMLDSIG, idem
- `NfseRepository`, `AmbienteGuard`, `ModuleConfig` — independem do provedor
- `Mappers` (Prestador, Tomador, Serviço, Tributo) — independem do provedor

---

## Etapas

### Etapa 1 — Interface única `ProviderInterface` (refactor)

**Arquivos:** `src/NfseNacional/Fiscal/ProviderInterface.php`

A interface atual já cobre todas as operações. Pequeno ajuste: adicionar
`getApiEndpoints(): ApiEndpointsInterface` para que os services não precisem
instanciar `ApiEndpoints` diretamente.

Métodos da interface (já existentes, sem alteração de assinatura):

```php
interface ProviderInterface
{
    public function emitirDps(string $dpsXml): ApiResponse;
    public function consultarNfse(string $chaveAcesso): ApiResponse;
    public function consultarPorProtocolo(string $protocolo): ApiResponse;
    public function cancelar(string $chaveAcesso, string $eventoXml): ApiResponse;
    public function obterDanfse(string $chaveAcesso): ApiResponse;
    public function obterXml(string $chaveAcesso): ApiResponse;
}
```

| Risco | Baixo — a interface já existe e é usada |
| --- | --- |
| **Alvo:** | `src/NfseNacional/Fiscal/ProviderInterface.php` |

---

### Etapa 2 — Provider Registry + Factory

**Arquivos novos:**
- `src/NfseNacional/Fiscal/ProviderRegistry.php`
- `src/NfseNacional/Fiscal/ProviderFactory.php`

```php
// ProviderRegistry — mapeia chave → classe
class ProviderRegistry
{
    private array $providers = [];

    public function register(string $key, string $class): void;
    public function has(string $key): bool;
    public function keys(): array;
    public function classFor(string $key): string;
}

// ProviderFactory — instancia o provider com as dependências corretas
class ProviderFactory
{
    public function __construct(
        private ProviderRegistry $registry,
        private ModuleConfig $config,
        private ?AmbienteGuard $guard = null,
    ) {}

    public function create(): ProviderInterface;
}
```

A factory lê `$this->config->get('provedor', 'sefin')` e instancia a classe
registrada para aquela chave.

**Registro padrão (boot):**

```php
// Em Bootstrap::boot() ou em ProviderRegistry::__construct()
$registry->register('sefin',       NacionalProvider::class);
$registry->register('notacontrol', NotaControlProvider::class);
```

| Risco | Baixo — padrão Registry + Factory bem estabelecido |
| --- | --- |
| **Alvo:** | `src/NfseNacional/Fiscal/ProviderRegistry.php`, `ProviderFactory.php` |

---

### Etapa 3 — Trocar type-hints de `NacionalProvider` → `ProviderInterface`

**Arquivos modificados:**
- `src/NfseNacional/Domain/Service/EmissaoService.php`
- `src/NfseNacional/Domain/Service/CancelamentoService.php`
- `src/NfseNacional/Domain/Service/ConsultaService.php`

Mudança mecânica em cada service:

```php
// Antes
use GK2\NfseNacional\Fiscal\NacionalProvider;
private NacionalProvider $provider;
public function __construct(?NacionalProvider $provider = null) {
    $this->provider = $provider ?? new NacionalProvider(...);
}

// Depois
use GK2\NfseNacional\Fiscal\ProviderInterface;
use GK2\NfseNacional\Fiscal\ProviderFactory;
private ProviderInterface $provider;
public function __construct(?ProviderInterface $provider = null) {
    $this->provider = $provider ?? (new ProviderFactory(...))->create();
}
```

| Risco | Baixo — troca de tipo sem mudança de comportamento |
| --- | --- |
| **Alvos:** | `EmissaoService.php`, `CancelamentoService.php`, `ConsultaService.php` |

---

### Etapa 4 — Remover `ApiEndpoints` direto do `EmissaoService`

**Problema:** `EmissaoService::processarEmissao()` (linhas ~150-160) instancia
`new ApiEndpoints()` diretamente para gerar URLs de DANFSe e XML.

**Solução:** Adicionar método `getDanfseUrl(string $chaveAcesso): string` e
`getXmlUrl(string $chaveAcesso): string` ao `ProviderInterface`, ou expor via
`getApiEndpoints()`.

```php
// Antes (EmissaoService)
$endpoints = new ApiEndpoints();
$danfseUrl = $endpoints->obterDanfse($ambiente, $chaveAcesso);

// Depois
$danfseUrl = $this->provider->getDanfseUrl($chaveAcesso);
```

| Risco | Médio — altera lógica de geração de URLs persistidas no banco |
| --- | --- |
| **Alvo:** | `EmissaoService.php`, `ProviderInterface.php` |

---

### Etapa 5 — Implementar `NotaControlProvider`

**Arquivo novo:** `src/NfseNacional/Fiscal/NotaControl/NotaControlProvider.php`

Este é o provider SOAP para emissores baseados na plataforma Nota Control /
ISS.net Online (Ribeirão Preto e outros municípios).

#### 5.1 Transporte: SOAP sobre HTTPS

```php
class NotaControlProvider implements ProviderInterface
{
    // Endpoints (definidos no manual v1.01):
    // Homologação: https://nfse.issnetonline.com.br/wsnfsenacional/homologacao/nfse.asmx
    // Produção:    https://nfse.issnetonline.com.br/wsnfsenacional/nfse.asmx
    
    private function getBaseUrl(): string
    {
        $suffix = $this->ambiente->isProducao() ? '' : '/homologacao';
        return "https://nfse.issnetonline.com.br/wsnfsenacional{$suffix}/nfse.asmx";
    }
}
```

#### 5.2 Envelope SOAP

Cada chamada SOAP envolve o XML fiscal em um envelope:

```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
    <cabecalho versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
      <versaoDados>1.01</versaoDados>
    </cabecalho>
  </soap:Header>
  <soap:Body>
    <GerarNfseEnvio xmlns="http://www.sped.fazenda.gov.br/nfse">
      <!-- DPS XML aqui -->
    </GerarNfseEnvio>
  </soap:Body>
</soap:Envelope>
```

#### 5.3 Método `emitirDps()` — fluxo

```
1. Recebe XML da DPS (já assinado pelo XmlSigner)
2. Monta envelope SOAP com <GerarNfseEnvio>
3. Envia POST para nfse.asmx (SOAPAction: GerarNfse)
4. Parseia resposta SOAP → extrai chaveAcesso, nfseXml
5. Retorna ApiResponse padronizado
```

#### 5.4 Autenticação

Mesmo certificado A1 usado para assinatura XML. O Nota Control autentica pela
assinatura XMLDSIG no documento (não por mTLS no request HTTP). O HTTP é HTTPS
simples com verificação de certificado do servidor.

#### 5.5 Métodos a implementar

| Método | SOAP Action | XML Envio | XML Resposta |
| --- | --- | --- | --- |
| `emitirDps` | `GerarNfse` | `GerarNfseEnvio` | `GerarNfseResposta` |
| `consultarNfse` | `ConsultarNfseDps` | `ConsultarNfseDpsEnvio` | `ConsultarNfseDpsResposta` |
| `consultarPorProtocolo` | `ConsultarLoteDps` | `ConsultarLoteDpsEnvio` | `ConsultarLoteDpsResposta` |
| `cancelar` | `CancelarNfse` | `CancelarNfseEnvio` | `CancelarNfseResposta` |
| `obterDanfse` | `ConsultarUrlNfse` | `ConsultarUrlNfseEnvio` | `ConsultarUrlNfseResposta` |
| `obterXml` | `ConsultarNfseDps` | `ConsultarNfseDpsEnvio` | `ConsultarNfseDpsResposta` |

| Risco | Alto — provider novo, protocolo diferente (SOAP vs REST) |
| --- | --- |
| **Alvo:** | `src/NfseNacional/Fiscal/NotaControl/NotaControlProvider.php` ✅ Implementado |
| **Depende de:** | cURL nativo do WHMCS + DOMDocument (sem `ext-soap`) |

#### 5.6 Transporte — Decisão de implementação

**Envelope SOAP via interpolação de string** (template estático) + **envio via
cURL** (biblioteca nativa do WHMCS). Sem dependência de `ext-soap`.

```php
private function sendSoap(string $soapAction, string $xmlBody): ApiResponse
{
    $envelope = $this->buildEnvelope($xmlBody);
    
    $ch = curl_init($this->getBaseUrl());
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $envelope);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml; charset=utf-8',
        'SOAPAction: ' . $soapAction,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $this->parseSoapResponse($httpCode, $body);
}
```

O envelope é uma string fixa com placeholder `{body}`:

```php
private const ENVELOPE = <<<'XML'
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
    <cabecalho versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
      <versaoDados>1.01</versaoDados>
    </cabecalho>
  </soap:Header>
  <soap:Body>{body}</soap:Body>
</soap:Envelope>
XML;
```

Vantagens: zero dependências externas, portabilidade total, o XML assinado
permanece íntegro (sem manipulação por bibliotecas SOAP).

---

### Etapa 6 — Configuração no admin (campo "Provedor")

**Arquivo modificado:** `src/NfseNacional/Admin/ConfigFields.php`

Adicionar dropdown no bloco de configurações:

```php
'provedor' => [
    'FriendlyName' => $fn('Provedor NFS-e', true),
    'Type'         => 'dropdown',
    'Options'      => 'sefin-Sefin Nacional (padrão),notacontrol-Nota Control / ISS.net',
    'Description'  => 'Selecione o provedor fiscal do seu município.'
        . $tip('Provedor', '...'),
    'Default'      => 'sefin',
],
```

**Arquivo modificado:** `src/NfseNacional/Config/ModuleConfig.php`

Adicionar getter:

```php
public function getProvedor(): string
{
    return $this->get('provedor', 'sefin');
}
```

| Risco | Baixo — adição de campo sem alteração de schema |
| --- | --- |
| **Alvos:** | `ConfigFields.php`, `ModuleConfig.php` |

---

### Etapa 7 — Testes

**Arquivos novos:**
- `tests/Unit/Fiscal/ProviderRegistryTest.php`
- `tests/Unit/Fiscal/ProviderFactoryTest.php`
- `tests/Integration/Fiscal/NotaControl/NotaControlProviderTest.php`

Testes unitários para o registry + factory com mock de `ModuleConfig`.
Testes de integração para o `NotaControlProvider` com mock das chamadas SOAP
(via Guzzle MockHandler — mesmo usando SOAP, o transporte ainda é HTTP).

---

## Diagrama pós-refactor

```
┌─────────────────────────────────────────────────┐
│ ConfigFields.php                                │
│   provedor: "sefin" | "notacontrol" | "futuro"  │
└──────────────────────┬──────────────────────────┘
                       │ ModuleConfig::getProvedor()
                       ▼
┌─────────────────────────────────────────────────┐
│ ProviderFactory::create()                       │
│   ┌─────────────────────────────────────────┐   │
│   │ ProviderRegistry                        │   │
│   │   'sefin'       → NacionalProvider      │   │
│   │   'notacontrol' → NotaControlProvider   │   │
│   │   'futuro'      → FuturoProvider        │   │
│   └─────────────────────────────────────────┘   │
└──────────────────────┬──────────────────────────┘
                       │ ProviderInterface
                       ▼
┌─────────────────────────────────────────────────┐
│ Domain/Service/                                 │
│   EmissaoService                               │
│   CancelamentoService    → usam ProviderInterface│
│   ConsultaService                              │
└──────────────────────┬──────────────────────────┘
                       │
          ┌────────────┴────────────┐
          ▼                         ▼
┌──────────────────┐    ┌──────────────────────────┐
│ NacionalProvider │    │ NotaControlProvider       │
│ (REST + JSON)    │    │ (SOAP + XML)              │
│                  │    │                           │
│ Sefin Nacional   │    │ nfse.issnetonline.com.br  │
│ *.nfse.gov.br    │    │ Nota Control / ISS.net    │
└──────────────────┘    └──────────────────────────┘
          │                         │
          └────────────┬────────────┘
                       │ compartilhado
                       ▼
┌─────────────────────────────────────────────────┐
│ Fiscal/Payload/                                 │
│   DpsPayloadBuilder   → mesmo XML (namespace SPED)│
│   EventoPayloadBuilder → mesmo XML              │
│ Fiscal/Signer/                                  │
│   XmlSigner           → mesma assinatura XMLDSIG │
└─────────────────────────────────────────────────┘
```

---

## Resumo de impacto

| Camada | Arquivos tocados | Risco |
| --- | --- | --- |
| **Interface** | `ProviderInterface.php` (ajuste) | Baixo |
| **Registry + Factory** | 2 arquivos novos | Baixo |
| **Services** | `EmissaoService`, `CancelamentoService`, `ConsultaService` (type-hint) | Baixo |
| **Config** | `ConfigFields.php`, `ModuleConfig.php` (campo novo) | Baixo |
| **NotaControlProvider** | 1 arquivo novo + possíveis auxiliares (SOAP client) | **Alto** |
| **Testes** | 3 arquivos novos | Baixo |
| **Compartilhado** | `DpsPayloadBuilder`, `EventoPayloadBuilder`, `XmlSigner`, `Mappers` | **Nenhum** |

---

## Perguntas em aberto

1. **`ext-soap` do PHP ou SOAP manual?** ✅ Decidido: envelope via interpolação
   de string (estático) + envio via cURL nativo do WHMCS. Sem dependência de
   `ext-soap`. Portabilidade máxima.

2. **Homologação Nota Control:** ✅ Solicitado ao contador acesso ao ambiente
   de homologação (tel: (67) 3041-2075, conforme manual seção 5.3). Aguardando
   retorno.

3. **Campo "Provedor" — escopo:** ✅ Decidido: configuração global no
   ModuleConfig (addon), não varia por cliente. Dropdown único no admin.

4. **Municípios Nota Control:** ✅ Confirmado: endpoint único
   `nfse.issnetonline.com.br`. A identificação do município é feita pelo código
   IBGE **dentro do XML** (campos `cLocEmi`, DPS Id prefix). O
   `DpsPayloadBuilder` já injeta corretamente `getCodigoMunicipioPrestador()`
   em `cLocEmi` (linha 112) e no Id da DPS (linha 68). O campo de configuração
   "Código IBGE do Município" deve conter `3543402` (Ribeirão Preto) — já
   validado.
