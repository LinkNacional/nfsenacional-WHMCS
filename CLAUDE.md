# CLAUDE.md — NFS-e Nacional (WHMCS Addon)

## Visão Geral

Addon WHMCS para emissão, consulta e cancelamento de **NFS-e Nacional** via API SEFIN/ADN do governo federal (`sefin.nfse.gov.br`). Namespace PHP: `GK2\NfseNacional`. Versão do XSD: **1.01**.

---

## Estrutura de Diretórios

```
modules/addons/nfsenacional/
├── nfsenacional.php          # Entry point do addon (config, output, etc.)
├── hooks.php                 # Registro de hooks WHMCS
├── cron.php                  # Cron de emissão automática
├── composer.json             # Dependências: robrichards/xmlseclibs (assinatura XML)
├── docs/
│   ├── API NFS-e - Sefin Nacional (v1).json      # Swagger oficial SEFIN ← CONSULTE AQUI
│   ├── API NFS-e - ADN Contribuinte (v1).json
│   ├── API NFS-e - ADN DANFSe (v1).json
│   ├── leiaute-eventos-nfse.md                    # Leiaute XML dos eventos (cancelamento, etc.)
│   ├── exemplo-emissao.xml                        # Exemplo real de NFS-e emitida
│   └── nfse-esquemas_xsd-prodrest-v1-01-*/       # XSDs oficiais v1.00 e v1.01
└── src/NfseNacional/
    ├── Bootstrap.php
    ├── Admin/
    │   ├── ConfigFields.php      # Definição dos campos de configuração do addon
    │   ├── AdminController.php
    │   └── Action/
    │       ├── EmitirAction.php
    │       ├── CancelarAction.php
    │       ├── ExcluirAction.php
    │       └── ReenviarEmailAction.php
    ├── ClientArea/
    │   └── ClientAreaController.php
    ├── Config/
    │   └── ModuleConfig.php      # Lê configurações do addon via tbladdonmodules
    ├── Domain/
    │   ├── AmbienteGuard.php     # Singleton — congela o ambiente (homolog/produção)
    │   ├── AmbienteMismatchException.php
    │   ├── Entity/
    │   │   ├── Nfse.php          # Entidade principal (status, chaveAcesso, etc.)
    │   │   └── Dps.php
    │   ├── Enum/
    │   │   ├── Ambiente.php      # homologacao | producao
    │   │   ├── NfseStatus.php    # PROCESSANDO | AUTORIZADA | CANCELADA | ERRO
    │   │   └── EmissaoPolitica.php
    │   └── Service/
    │       ├── EmissaoService.php      # Orquestra emissão DPS → NFS-e
    │       ├── CancelamentoService.php # Orquestra cancelamento via evento e101101
    │       ├── ConsultaService.php
    │       └── EmailService.php
    ├── Fiscal/
    │   ├── ProviderInterface.php
    │   ├── NacionalProvider.php        # HTTP client wrapper (emitir, cancelar, consultar)
    │   ├── Signer/
    │   │   └── XmlSigner.php           # Assinatura XML com xmlseclibs (RSA-SHA256)
    │   ├── Payload/
    │   │   ├── DpsPayloadBuilder.php   # Gera XML da DPS (XSD v1.01), assina, retorna string
    │   │   └── EventoPayloadBuilder.php # Gera XML do pedRegEvento (cancelamento e101101)
    │   └── Mapper/
    │       ├── PrestadorMapper.php     # Dados do prestador (CNPJ, IM, Simples)
    │       ├── TomadorMapper.php       # Dados do tomador (CNPJ/CPF, endereço)
    │       ├── ServicoMapper.php       # Discriminação, valor, códigos fiscais
    │       └── TributoMapper.php       # ISS, PIS/COFINS, retenções
    ├── Hook/
    │   ├── AdminInvoiceUI.php          # Painel NFS-e na tela da fatura (admin)
    │   ├── HookHandler.php
    │   ├── InvoiceHooks.php
    │   └── ClientAreaMenu.php
    ├── Persistence/
    │   ├── NfseRepository.php          # CRUD na tabela tblnfsenacional
    │   ├── DpsSequence.php             # Sequência numérica do DPS (isolada por série+ambiente)
    │   └── Migration.php
    └── Transport/
        ├── HttpClient.php              # Guzzle/cURL com mTLS, retry em 429/503
        ├── ApiEndpoints.php            # URLs de todos os endpoints SEFIN/ADN
        ├── ApiResponse.php
        └── Auth/
            ├── CertificateAuth.php     # mTLS via PKCS12 ou PEM
            └── TokenAuth.php
```

---

## Fluxo de Emissão (DPS → NFS-e)

```
InvoiceHooks / EmitirAction
    → EmissaoService::processarEmissao()
        → DpsPayloadBuilder::build()         # Gera XML DPS (XSD v1.01)
            → PrestadorMapper::map()
            → TomadorMapper::map()
            → ServicoMapper::map()
            → TributoMapper::map()
            → XmlSigner::signDom()           # Assina <infDPS> com certificado
        → NacionalProvider::emitirDps()      # GZip + Base64 → POST JSON
            POST /nfse {"dpsXmlGZipB64": "..."}
        → NfseRepository::createOrUpdate()   # Salva status, chaveAcesso, etc.
```

## Fluxo de Cancelamento (Evento e101101)

```
CancelarAction
    → CancelamentoService::cancelar()
        → EventoPayloadBuilder::buildCancelamento()  # Gera XML pedRegEvento
            → XmlSigner::signDom()                    # Assina <infPedReg>
        → NacionalProvider::cancelar()               # GZip + Base64 → POST JSON
            POST /nfse/{chaveAcesso}/eventos
            {"pedidoRegistroEventoXmlGZipB64": "..."}
        → NfseRepository::updateStatus(CANCELADA)
```

---

## Formato dos Identificadores XML

### DPS (`infDPS.Id`)
```
DPS + CodMun(7) + tpInscFed(1) + InscFederal(14) + Serie(5) + NumDPS(15) = 45 chars
     └─ IBGE 7d ┘ └ 1=CPF/2=CNPJ ┘ └── pad left 0 ─┘ └─ pad 0─┘ └── pad left 0 ──┘
```
- `tpInscFed`: **1=CPF**, **2=CNPJ** (atenção: 1 NÃO é CNPJ)
- InscFederal: sempre 14 dígitos (CPF é left-padded com zeros)

### pedRegEvento (`infPedReg.Id`)
```
PRE + chaveAcesso(50) + codEvento(6) = 59 chars
```
- codEvento cancelamento: `101101`

### Chave de acesso NFS-e
- **50 dígitos numéricos** — verificar `strlen` antes de montar qualquer ID

---

## API — Endpoints SEFIN Nacional

| Operação | Método | Path |
|----------|--------|------|
| Emitir DPS | POST | `/nfse` |
| Consultar NFS-e | GET | `/nfse/{chaveAcesso}` |
| Registrar Evento | POST | `/nfse/{chaveAcesso}/eventos` |
| Consultar Evento | GET | `/nfse/{chaveAcesso}/eventos/{tipoEvento}/{numSeqEvento}` |
| Consultar DPS | GET | `/dps/{id}` |

- **Homologação**: `sefin.producaorestrita.nfse.gov.br/SefinNacional`
- **Produção**: `sefin.nfse.gov.br/SefinNacional` (+ domínio próprio configurável)
- **DANFSE**: `adn.{dominio}/danfse/{chaveAcesso}`

### Formato de transporte
- DPS: `{"dpsXmlGZipB64": "<gzip+base64 do XML>"}` — campo **dpsXmlGZipB64**
- Evento: `{"pedidoRegistroEventoXmlGZipB64": "<gzip+base64 do XML>"}` — campo **pedidoRegistroEventoXmlGZipB64**
- Resposta sucesso: campo **nfseXmlGZipB64** (NFS-e autorizada)
- Autenticação: mTLS obrigatório (certificado ICP-Brasil A1/A3)

---

## Campos de Configuração Importantes

| Chave (tbladdonmodules) | Getter | Uso no XML |
|-------------------------|--------|------------|
| `cnpj_prestador` | `getCnpjPrestador()` | `<CNPJ>` no prestador, `CNPJAutor` no evento |
| `codigoservico` | `getCodigoServico()` | `<cTribNac>` (6 dígitos, LC 116) — OBRIGATÓRIO. Ex: `010700` para item 1.07 |
| `codigo_servico_nacional` | `getCodigoServicoNacional()` | `<cNBS>` (9 dígitos, NBS) — **OPCIONAL** no XSD (`minOccurs="0"`), deixar vazio se não souber |
| `codigomunicipal` | `getCodigoMunicipal()` | `<cTribMun>` (3 dígitos exatos) — código específico de cada município. Se comprimento ≠ 3, é omitido e logado via `logActivity`. Deixar vazio evita E0314. |
| `exigibilidade_iss` | `getExigibilidadeIss()` | Exigibilidade do ISS: 1=Exigível, 2=Não Incidência, 3=Isenção, 4=Exportação, 5=Imunidade, 6-7=Suspensa |
| `codigo_municipio_prestador` | `getCodigoMunicipioPrestador()` | `<cLocEmi>` / `<cLocPrestacao>` (7 dígitos IBGE) — sem default; **obrigatório configurar** |
| `inscricao_municipal` | `getInscricaoMunicipal()` | `<IM>` no prestador — enviado somente se não vazio. Deixar vazio em municípios que rejeitam (erro E0120) |
| `regime_tributario` | — | Define `opSimpNac`: 4=MEI(2), 1-2+optante=ME/EPP(3), resto=1 |
| `reg_ap_trib_sn` | `getRegApTribSN()` | `<regApTribSN>`: 1=Competência, 2=Caixa (obrigatório para ME/EPP e MEI) |
| `reg_esp_trib` | `getRegEspTrib()` | `<regEspTrib>`: 0=Nenhum, 1=Estimativa Anual, 2=Autônomo, 3=Soc. Profissionais, 4=Cooperativa, 5=MEI, 6=ME-EPP SN |
| `ibscbs_cind_op` | — | `<cIndOp>` no bloco IBSCBS (padrão `050101`) |
| `ibscbs_cst` | — | `<CST>` no bloco IBSCBS (padrão `000`) |
| `ibscbs_cclass_trib` | — | `<cClassTrib>` no bloco IBSCBS (padrão `000001`) |
| `ambiente` | `getAmbiente()` | `homologacao` ou `producao` |
| `certificado_path` | `getCertificadoPath()` | Caminho do PFX/P12 ou PEM |
| `certificado_senha` | `getCertificadoSenha()` | Senha do certificado |

---

## Regras de Negócio Críticas

### Ambiente
- `AmbienteGuard` é **singleton** — congela o ambiente na primeira instância.
- Todos os registros na `tblnfsenacional` têm coluna `ambiente`.
- Queries sempre filtram por ambiente ativo — **nunca misturar prod/homolog**.

### Assinatura Digital
- Assina `<infDPS>` (DPS) e `<infPedReg>` (eventos) com RSA-SHA256.
- Certificado PKCS12 (.pfx/.p12) ou PEM.
- Biblioteca: `robrichards/xmlseclibs` (via Composer no diretório do módulo).
- `verAplic` tem **max 20 caracteres** (XSD `TSVerAplic`). Usar `WHMCS-NfseNac-1.0`.

### IBSCBS (Reforma Tributária — IBS/CBS)
- Bloco `<IBSCBS>` dentro do `<infDPS>` é **obrigatório a partir de 2026** no XSD v1.01.
- Campos: `finNFSe=0`, `cIndOp`, `indDest=0`, `valores/trib/gIBSCBS/{CST, cClassTrib}`.

### NBS (Nomenclatura Brasileira de Serviços)
- Campo `<cNBS>` é **OPCIONAL**.
- 9 dígitos numéricos, padded à **direita** com zeros (`STR_PAD_RIGHT`).
- Se o código configurado não existir na tabela NBS → erro **E0316**.
- Solução: deixar o campo `codigo_servico_nacional` **vazio** nas configurações.

### Simples Nacional
- `opSimpNac`: 1=Não Optante, 2=MEI, 3=ME/EPP
- `regApTribSN` é obrigatório quando `opSimpNac` é 2 ou 3 (erro **E0166** se ausente).

### Inscrição Municipal (IM)
- Alguns municípios não têm IM cadastrado no CNC NFS-e → erro **E0120**.
- `<IM>` é enviado somente se o campo `inscricao_municipal` estiver preenchido.
- Para evitar E0120: deixar o campo `inscricao_municipal` **vazio** nas configurações do addon.

### Discriminação do serviço (`xDescServ`)
- Não pode conter quebras de linha (`\n`, `\r`) → erro **E999**.
- `ServicoMapper::sanitizeDiscriminacao()` substitui `\n` por espaço.

### Sequência DPS
- `DpsSequence` garante sequência isolada por `serie + ambiente`.
- Nunca reutilizar número DPS — o ID da DPS é único no sistema nacional.

---

## Erros Comuns e Causas

| Código | Causa | Solução |
|--------|-------|---------|
| **E0004** | `tpInscFed` errado no Id da DPS | 1=CPF, 2=CNPJ (CNPJ tem 14 dígitos) |
| **RNG6110** | Falha de schema XML | Verificar tamanho/formato de campos; `verAplic` ≤ 20 chars |
| **E0120** | `<IM>` enviado mas município não tem IM no CNC | Deixar `inscricao_municipal` vazio nas configurações do addon |
| **E0314** | `cTribMun` inválido ou não administrado pelo município | Verificar código no portal NFS-e da prefeitura; se em dúvida, deixar `codigomunicipal` vazio |
| **E0166** | `regApTribSN` ausente para Simples Nacional | Configurar `reg_ap_trib_sn` (1=Competência, 2=Caixa) |
| **E0316** | Código NBS inexistente na tabela | Deixar `codigo_servico_nacional` vazio (campo opcional) |
| **E0812** | CNPJ do autor não corresponde ao certificado | `CNPJAutor` no evento deve ser o CNPJ do certificado digital |
| **E0822** | Cancelamento fora do prazo | Prazo parametrizado pelo município emissor |
| **E999** | Erro não catalogado | Verificar `\n` na discriminação, padding do NBS, campos obrigatórios |
| **E1989** | Assinatura obrigatória | Certificado digital não configurado ou XmlSigner falhou |

---

## Tabela do Banco de Dados

**`tblnfsenacional`** — registros de NFS-e por fatura WHMCS:

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | int | PK |
| `invoice_id` | int | FK para tblinvoices |
| `id_client` | int | FK para tblclients |
| `ambiente` | varchar | `homologacao` \| `producao` |
| `status` | varchar | NfseStatus enum |
| `chave_acesso` | varchar(50) | Chave de acesso da NFS-e |
| `numero_nfse_nacional` | varchar | Número da NFS-e |
| `numero_dps` | int | Número sequencial da DPS |
| `serie_dps` | varchar | Série da DPS |
| `protocolo` | varchar | idDps retornado pela API |
| `data_emissao` | datetime | |
| `data_autorizacao` | datetime | |
| `erro` | text | Mensagem de erro (status ERRO) |
| `xml_retorno` | text | nfseXmlGZipB64 da resposta |
| `danfse_url` | varchar | URL do DANFS-e |
| `xml_url` | varchar | URL do XML |

**`mod_nfsenacional_grupo`** — códigos fiscais por grupo de produto:

| Coluna | Descrição |
|--------|-----------|
| `idgrupo` | ID do grupo WHMCS (tblproductgroups) |
| `codigo_servico_nacional` | NBS do grupo |
| `codigoatividade` | cTribNac do grupo |
| `codigomunicipal` | cTribMun do grupo |
| `cnae` | CNAE do grupo |

---

## Documentação de Referência

Todos os arquivos estão em `docs/`:

- **`API NFS-e - Sefin Nacional (v1).json`** — Swagger completo. Consultar para:
  - Nome exato dos campos JSON de request/response
  - Códigos HTTP esperados
  - Estrutura de erros (`erros[]`) e alertas (`alertas[]`)

- **`leiaute-eventos-nfse.md`** — Leiaute XML dos eventos (cancelamento, manifestação, etc.). Consultar para:
  - Estrutura do `pedRegEvento` e `infPedReg`
  - Campos obrigatórios por tipo de evento
  - Regras de negócio (RNs) e códigos de erro

- **`nfse-esquemas_xsd-prodrest-v1-01-*/Schemas/1.01/`** — XSDs oficiais v1.01. Consultar para:
  - Validação de tipos/tamanhos de campos
  - Sequência obrigatória de elementos
  - Constraints de pattern (ex: `DPS[0-9]{42}`, `TSVerAplic` max 20)

- **`exemplo-emissao.xml`** — XML real de NFS-e com IBSCBS incluído.
