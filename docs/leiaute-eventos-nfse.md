# Leiaute de Eventos NFS-e — Sistema Nacional

> Fonte: `anexo_ii-sefin_adn-pedregevt_evt-snnfse-prodrest-v1-01-202601122.xlsx`

---

## Sumário

1. [Tipos de Eventos de NFS-e](#1-tipos-de-eventos-de-nfs-e)
2. [Estrutura do XML — Evento / Pedido de Registro de Evento](#2-estrutura-do-xml--evento--pedido-de-registro-de-evento)
   - [Parte Geral](#21-parte-geral)
   - [Partes Específicas por Tipo de Evento](#22-partes-específicas-por-tipo-de-evento)
3. [Regras de Compatibilidade entre Eventos (RN EVENTOSxEVENTOS)](#3-regras-de-compatibilidade-entre-eventos)
4. [Regras de Negócio dos Campos XML](#4-regras-de-negócio-dos-campos-xml)
5. [Legendas e Glossário](#5-legendas-e-glossário)

---

## 1. Tipos de Eventos de NFS-e

### Código do Evento

O código identificador do evento é formado por **6 dígitos**:

| Posição | Significado |
|---------|-------------|
| 1º dígito | Categoria do evento |
| 2º e 3º dígitos | Autor(es) do evento |
| 4º dígito | Ambiente receptor |
| 5º e 6º dígitos | Número sequencial por categoria |

### Autores do Pedido de Registro de Evento

| Código | Sigla | Descrição |
|--------|-------|-----------|
| 01 | Emite | Emitente da NFS-e |
| 02 | Prestador | Prestador de serviço |
| 03 | Tomador | Tomador de serviço |
| 04 | Intermediário | Intermediário |
| 05 | MEmis | Município Emissor |
| 06 | MIncid | Município de Incidência |
| 07 | Man | Módulo de Apuração Nacional |
| 08 | RespTrib | Responsável Tributário |
| 56 | MEmis \| MInci | Município Emissor ou de Incidência |
| 67 | MInci \| Man | Município de Incidência ou Módulo Nacional |
| 99 | CGNFSe | Comitê Gestor da NFS-e |

### Visibilidade

| Sigla | Descrição |
|-------|-----------|
| EM | Emitente da NFS-e |
| NE | Não Emitente |
| SP | Sujeito Passivo |
| CP | Consulta Pública |
| AT | Administração Tributária* |

> *Município Emissor, Município(s) do(s) Não Emitente(s), Município de Incidência do ISSQN e Município do local da prestação do serviço.

### Tabela de Eventos

| # | Evento | Código (Cat./Autor/Amb./Seq.) | Categoria | Autor | Assinatura Digital | Ambiente Receptor | NFS-e no ADN? | Único? | Visibilidade |
|---|--------|-------------------------------|-----------|-------|--------------------|-------------------|---------------|--------|--------------|
| 1 | Cancelamento de NFS-e | 1 01 1 01 | 1 - Cancelamentos | Emite | Sim | Sistema que gerou a NFS-e | Sim | Sim | EM/NE/CP/AT |
| 2 | Cancelamento de NFS-e por Substituição | 1 05 1 02 | 1 - Cancelamentos | MEmis | — | Sistema que gerou a NFS-e | Sim | Sim | EM/NE/CP/AT |
| 3 | Solicitação de Análise Fiscal para Cancelamento de NFS-e | 1 01 1 03 | 1 - Cancelamentos | Emite | Sim | Sistema que gerou a NFS-e | Sim | Sim | EM/NE/AT |
| 4 | Cancelamento de NFS-e Deferido por Análise Fiscal | 1 05 1 04 | 1 - Cancelamentos | MEmis | — | Sistema que gerou a NFS-e | Sim | Sim | EM/NE/AT |
| 5 | Cancelamento de NFS-e Indeferido por Análise Fiscal | 1 05 1 05 | 1 - Cancelamentos | MEmis | — | Sistema que gerou a NFS-e | Sim | Sim | EM/NE/AT |
| 6 | Manifestação de NFS-e — Confirmação do Prestador | 2 02 2 01 | 2 - Manifestações | Emite (Prestador) | Não | ADN | Sim | Sim | EM/NE/CP/AT |
| 7 | Manifestação de NFS-e — Confirmação do Tomador | 2 03 2 02 | 2 - Manifestações | Emite (Tomador) | Não | ADN | Sim | Sim | EM/NE/CP/AT |
| 8 | Manifestação de NFS-e — Confirmação do Intermediário | 2 04 2 03 | 2 - Manifestações | Emite (Intermediário) | Não | ADN | Sim | Sim | EM/NE/CP/AT |
| 9 | Manifestação de NFS-e — Confirmação Tácita | 2 05 2 04 | 2 - Manifestações | MIncid | — | Sistema que gerou / ADN | Sim | Sim | EM/NE/CP/AT |
| 10 | Manifestação de NFS-e — Rejeição do Prestador | 2 02 2 05 | 2 - Manifestações | Emite (Prestador) | Não | ADN | Sim | Sim | EM/NE/CP/AT |
| 11 | Manifestação de NFS-e — Rejeição do Tomador | 2 03 2 06 | 2 - Manifestações | Emite (Tomador) | Não | ADN | Sim | Sim | EM/NE/CP/AT |
| 12 | Manifestação de NFS-e — Rejeição do Intermediário | 2 04 2 07 | 2 - Manifestações | Emite (Intermediário) | Não | ADN | Sim | Sim | EM/NE/CP/AT |
| 13 | Manifestação de NFS-e — Anulação da Rejeição | 2 05 2 08 | 2 - Manifestações | MIncid | — | ADN | Sim | Sim | EM/NE/CP/AT |
| 14 | Cancelamento de NFS-e por Ofício | 3 05 1 01 | 3 - Ofícios | MEmis | — | Sistema que gerou a NFS-e | Sim | Sim | EM/NE/CP/AT |
| 15 | Bloqueio de NFS-e por Ofício | 3 05 1 02 | 3 - Ofícios | MEmis | — | Sistema que gerou a NFS-e | Sim | Não | EM/AT |
| 16 | Desbloqueio de NFS-e por Ofício | 3 05 1 03 | 3 - Ofícios | MEmis | — | Sistema que gerou a NFS-e | Sim | Não | EM/AT |

---

## 2. Estrutura do XML — Evento / Pedido de Registro de Evento

### Legenda dos Tipos de Elementos

| ELE | Significado |
|-----|-------------|
| Raiz | TAG raiz do XML |
| A | Atributo XML |
| E | Elemento filho |
| G | Grupo de elementos |
| ID | Campo identificador |
| CE | Campo exclusivo (choice element) |
| CG | Grupo exclusivo (choice group) |

### Legenda dos Tipos de Dados

| TIPO | Significado |
|------|-------------|
| C | Alfanumérico |
| N | Numérico |
| D | Data/hora ou Domínio (enumerado) |
| G | Grupo |

### 2.1 Parte Geral

#### `evento` (raiz)

```xml
<evento versao="...">
  <infEvento id="EVT...">
    ...
  </infEvento>
  <Signature>...</Signature>   <!-- assinatura obrigatória -->
</evento>
```

| # | Caminho | Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|---|---------|-------|-----|------|-------|---------|-----------|
| 1 | — | `evento` | Raiz | — | — | — | TAG raiz |
| 2 | `evento/` | `versao` | A | C | 1-1 | 1-4V2 | Versão do leiaute do evento |
| 3 | `evento/` | `infEvento` | G | — | 1-1 | — | Grupo de informações do pedido de registro do evento |
| 4 | `evento/infEvento/` | `id` | ID | C | 1-1 | 62 | Identificador: `"EVT"` + id do PRE (56 dígitos) + nSeqEvento (3 dígitos) |
| 5 | `evento/infEvento/` | `verAplic` | E | C | 1-1 | 1-20 | Versão do aplicativo que gerou o evento |
| 6 | `evento/infEvento/` | `ambGer` | E | D | 1-1 | — | Ambiente gerador: `1`=Sistema próprio do município, `2`=Sefin Nacional NFS-e, `3`=ADN NFS-e |
| 7 | `evento/infEvento/` | `nSeqEvento` | E | N | 1-1 | 3 | Número sequencial do evento para o mesmo tipo de evento |
| 8 | `evento/infEvento/` | `dhProc` | E | D | 1-1 | — | Data/Hora do registro do evento (UTC: `AAAA-MM-DDThh:mm:ssTZD`) |
| 9 | `evento/infEvento/` | `nDFSe` | E | N | 1-1 | 1-13 | Número sequencial do documento gerado pelo ambiente gerador de DFSe |
| 10 | `evento/infEvento/` | `pedRegEvento` | G | G | 1-1 | — | Leiaute do pedido de registro do evento |

#### `pedRegEvento`

```xml
<pedRegEvento versao="...">
  <infPedReg id="PRE...">
    ...
  </infPedReg>
  <Signature>...</Signature>   <!-- assinatura (0-1) -->
</pedRegEvento>
```

| # | Caminho | Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|---|---------|-------|-----|------|-------|---------|-----------|
| 11 | `evento/pedRegEvento/` | `versao` | A | C | 1-1 | 1-4V2 | Versão do leiaute do pedido de registro do evento |
| 12 | `evento/pedRegEvento/` | `infPedReg` | G | — | 1-1 | — | Parte Geral do Pedido de Registro de Evento |
| 13 | `evento/pedRegEvento/infPedReg/` | `id` | ID | C | 1-1 | 59 | Identificador: `"PRE"` + Chave de acesso da NFS-e (50) + Código do evento (6) |
| 14 | `evento/pedRegEvento/infPedReg/` | `tpAmb` | E | N | 1-1 | 1 | Tipo de ambiente: `1`=Produção, `2`=Homologação |
| 15 | `evento/pedRegEvento/infPedReg/` | `verAplic` | E | C | 1-1 | 1-20 | Versão do aplicativo que gerou o pedido de registro |
| 16 | `evento/pedRegEvento/infPedReg/` | `dhEvento` | E | D | 1-1 | — | Data e hora do evento (UTC: `AAAA-MM-DDThh:mm:ssTZD`). Ex.: `2010-08-19T13:00:15-03:00` |
| 17 | `evento/pedRegEvento/infPedReg/` | `CNPJAutor` | CE | N | 1-1 | 14 | CNPJ do autor do evento. Em eventos do fisco: CNPJ da prefeitura |
| 18 | `evento/pedRegEvento/infPedReg/` | `CPFAutor` | CE | N | 1-1 | 11 | CPF do autor do evento. Em eventos do fisco: não preencher |
| 19 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | CE | N | 1-1 | 50 | Chave de acesso da NFS-e à qual o evento será vinculado |
| 84 | `evento/pedRegEvento/` | `Signature` | G | — | 0-1 | — | Assinatura do pedido de registro (XML Digital Signature) |
| 85 | `evento/` | `Signature` | G | — | 1-1 | — | Assinatura do evento (XML Digital Signature) — obrigatória |

> **`CNPJAutor` e `CPFAutor` são mutuamente exclusivos (choice element).**

---

### 2.2 Partes Específicas por Tipo de Evento

Cada pedido de registro contém **apenas uma** das tags de parte específica (`CG` = choice group), conforme o tipo do evento.

---

#### Evento `e101101` — Cancelamento de NFS-e (Código: `101101`)

```xml
<e101101>
  <xDesc>Cancelamento de NFS-e</xDesc>
  <cMotivo>1</cMotivo>
  <xMotivo>Descrição do motivo...</xMotivo>
</e101101>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento: `"Cancelamento de NFS-e"` |
| `cMotivo` | E | N | 1-1 | 1 | `1`=Erro na Emissão, `2`=Serviço não Prestado, `9`=Outros |
| `xMotivo` | E | C | 1-1 | 15-255 | Descrição detalhada do motivo |

---

#### Evento `e105102` — Cancelamento de NFS-e por Substituição (Código: `105102`)

```xml
<e105102>
  <xDesc>Cancelamento de NFS-e por Substituição</xDesc>
  <cMotivo>01</cMotivo>
  <xMotivo>Descrição do motivo...</xMotivo>  <!-- opcional -->
  <chSubstituta>chave_da_nfse_substituta</chSubstituta>
</e105102>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento: `"Cancelamento de NFS-e por Substituição"` |
| `cMotivo` | E | N | 1-1 | 2 | `01`=Desenquadramento do Simples Nacional, `02`=Enquadramento no Simples Nacional, `03`=Inclusão Retroativa de Imunidade/Isenção, `04`=Exclusão Retroativa de Imunidade/Isenção, `05`=Rejeição do tomador/intermediário, `99`=Outros |
| `xMotivo` | E | C | 0-1 | 15-255 | Descrição detalhada do motivo (opcional) |
| `chSubstituta` | E | N | 1-1 | 50 | Chave de acesso da NFS-e substituta |

---

#### Evento `e101103` — Solicitação de Análise Fiscal para Cancelamento (Código: `101103`)

```xml
<e101103>
  <xDesc>Solicitação de Análise Fiscal para Cancelamento de NFS-e</xDesc>
  <cMotivo>1</cMotivo>
  <xMotivo>Descrição do motivo...</xMotivo>
</e101103>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `cMotivo` | E | N | 1-1 | 1 | `1`=Erro na Emissão, `2`=Serviço não Prestado, `9`=Outros |
| `xMotivo` | E | C | 1-1 | 15-255 | Descrição detalhada do motivo |

---

#### Evento `e105104` — Cancelamento de NFS-e Deferido por Análise Fiscal (Código: `105104`)

```xml
<e105104>
  <xDesc>Cancelamento de NFS-e Deferido por Análise Fiscal</xDesc>
  <CPFAgTrib>12345678901</CPFAgTrib>
  <nProcAdm>numero_processo</nProcAdm>  <!-- opcional -->
  <cMotivo>1</cMotivo>
  <xMotivo>Descrição do motivo...</xMotivo>
</e105104>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `CPFAgTrib` | E | N | 1-1 | 11 | CPF do agente tributário que efetuou o deferimento |
| `nProcAdm` | E | N | 0-1 | 1-30 | Número do processo administrativo municipal (opcional) |
| `cMotivo` | E | N | 1-1 | 1 | `1`=Cancelamento de NFS-e Deferido |
| `xMotivo` | E | C | 1-1 | 15-255 | Descrição detalhada do motivo |

---

#### Evento `e105105` — Cancelamento de NFS-e Indeferido por Análise Fiscal (Código: `105105`)

```xml
<e105105>
  <xDesc>Cancelamento de NFS-e Indeferido por Análise Fiscal</xDesc>
  <CPFAgTrib>12345678901</CPFAgTrib>
  <nProcAdm>numero_processo</nProcAdm>  <!-- opcional -->
  <cMotivo>1</cMotivo>
  <xMotivo>Descrição do motivo...</xMotivo>
</e105105>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `CPFAgTrib` | E | N | 1-1 | 11 | CPF do agente tributário que efetuou o indeferimento |
| `nProcAdm` | E | N | 0-1 | 1-30 | Número do processo administrativo municipal (opcional) |
| `cMotivo` | E | N | 1-1 | 1 | `1`=Cancelamento Indeferido, `2`=Indeferido Sem Análise de Mérito |
| `xMotivo` | E | C | 1-1 | 15-255 | Descrição detalhada do motivo |

---

#### Eventos de Manifestação — Confirmações (Códigos: `202201`, `203202`, `204203`, `205204`)

Estes eventos possuem apenas o campo `xDesc`:

```xml
<!-- Confirmação do Prestador -->
<e202201>
  <xDesc>Manifestação de NFS-e - Confirmação do Prestador</xDesc>
</e202201>

<!-- Confirmação do Tomador -->
<e203202>
  <xDesc>Manifestação de NFS-e - Confirmação do Tomador</xDesc>
</e203202>

<!-- Confirmação do Intermediário -->
<e204203>
  <xDesc>Manifestação de NFS-e - Confirmação do Intermediário</xDesc>
</e204203>

<!-- Confirmação Tácita -->
<e205204>
  <xDesc>Manifestação de NFS-e - Confirmação Tácita</xDesc>
</e205204>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento (texto fixo conforme tipo) |

---

#### Eventos de Manifestação — Rejeições (Códigos: `202205`, `203206`, `204207`)

```xml
<!-- Rejeição do Prestador -->
<e202205>
  <xDesc>Manifestação de NFS-e - Rejeição do Prestador</xDesc>
  <cMotivo>1</cMotivo>
  <xMotivo>Descrição...</xMotivo>  <!-- opcional, exceto quando cMotivo=9 -->
</e202205>

<!-- Rejeição do Tomador -->
<e203206>
  <xDesc>Manifestação de NFS-e - Rejeição do Tomador</xDesc>
  <cMotivo>1</cMotivo>
  <xMotivo>Descrição...</xMotivo>
</e203206>

<!-- Rejeição do Intermediário -->
<e204207>
  <xDesc>Manifestação de NFS-e - Rejeição do Intermediário</xDesc>
  <cMotivo>1</cMotivo>
  <xMotivo>Descrição...</xMotivo>
</e204207>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `cMotivo` | E | N | 1-1 | 1 | `1`=NFS-e em duplicidade, `2`=NFS-e já emitida pelo tomador, `3`=Não ocorrência do fato gerador, `4`=Erro de responsabilidade tributária, `5`=Erro no valor/deduções/serviço/data, `9`=Outros |
| `xMotivo` | E | C | 0-1 | 15-255 | Descrição detalhada (obrigatório quando `cMotivo=9`) |

---

#### Evento `e205208` — Anulação da Rejeição (Código: `205208`)

```xml
<e205208>
  <xDesc>Manifestação de NFS-e - Anulação da Rejeição</xDesc>
  <CPFAgTrib>12345678901</CPFAgTrib>
  <idEvManifRej>PRE...</idEvManifRej>
  <xMotivo>Descrição do motivo...</xMotivo>
</e205208>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `CPFAgTrib` | E | N | 1-1 | 11 | CPF do agente tributário que efetuou a anulação da rejeição |
| `idEvManifRej` | E | C | 1-1 | 59 | Referência ao `id` do evento de Rejeição que originou este evento |
| `xMotivo` | E | C | 1-1 | 15-255 | Descrição detalhada do motivo |

---

#### Evento `e305101` — Cancelamento de NFS-e por Ofício (Código: `305101`)

```xml
<e305101>
  <xDesc>Cancelamento de NFS-e por Ofício</xDesc>
  <CPFAgTrib>12345678901</CPFAgTrib>
  <nProcAdm>numero_processo</nProcAdm>
  <xProcAdm>Descrição do processo...</xProcAdm>
</e305101>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `CPFAgTrib` | E | N | 1-1 | 11 | CPF do agente tributário que efetuou o cancelamento por ofício |
| `nProcAdm` | E | N | 1-1 | 30 | Número do processo administrativo municipal |
| `xProcAdm` | E | C | 1-1 | 15-255 | Descrição do motivo do processo administrativo |

---

#### Evento `e305102` — Bloqueio de NFS-e por Ofício (Código: `305102`)

```xml
<e305102>
  <xDesc>Bloqueio de NFS-e por Ofício</xDesc>
  <CPFAgTrib>12345678901</CPFAgTrib>
  <codEvento>e101101</codEvento>
  <xMotivo>Descrição do motivo...</xMotivo>
</e305102>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `CPFAgTrib` | E | N | 1-1 | 11 | CPF do agente tributário que efetuou o bloqueio |
| `codEvento` | E | N | 1-1 | 7 | Evento a ser bloqueado: `e101101`, `e105102`, `e105104`, `e105105` ou `e305101` |
| `xMotivo` | E | C | 1-1 | 15-255 | Descrição detalhada do motivo |

---

#### Evento `e305103` — Desbloqueio de NFS-e por Ofício (Código: `305103`)

```xml
<e305103>
  <xDesc>Desbloqueio de NFS-e por Ofício</xDesc>
  <CPFAgTrib>12345678901</CPFAgTrib>
  <idBloqOfic>PRE...</idBloqOfic>
</e305103>
```

| Campo | ELE | Tipo | Ocor. | Tamanho | Descrição |
|-------|-----|------|-------|---------|-----------|
| `xDesc` | E | C | 1-1 | 5-60 | Descrição do evento |
| `CPFAgTrib` | E | N | 1-1 | 11 | CPF do agente tributário que efetuou o desbloqueio |
| `idBloqOfic` | E | C | 1-1 | 59 | Referência ao `id` do evento de Bloqueio que originou este desbloqueio |

---

## 3. Regras de Compatibilidade entre Eventos

A tabela abaixo indica quais eventos podem ser recepcionados pelo Sistema Nacional NFS-e dado que já existe um evento pré-existente vinculado à NFS-e.

- **V** = Permitido
- **X** = Não é Permitido
- **X/V** = Depende de condição específica (ver regras de negócio)

### Eventos que podem ser recebidos após cada pré-existente

| Evento Pré-Existente | Canc. NFS-e | Canc. por Subst. | Sol. Análise Fiscal | Deferido AF | Indeferido AF | Confirm. Prestador | Confirm. Tomador | Confirm. Intermed. | Confirm. Tácita | Rejeição Prestador | Rejeição Tomador | Rejeição Intermed. | Anulação Rejeição | Canc. por Ofício | Bloq. (e101101) | Bloq. (e105102) | Bloq. (e105104) | Bloq. (e105105) | Bloq. (e305101) | Desbloq. (e101101) | Desbloq. (e105102) | Desbloq. (e105104) | Desbloq. (e105105) | Desbloq. (e305101) |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **Nenhum evento** | V | V | V | X | X | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | X | X | X | X |
| **Cancelamento de NFS-e** | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| **Cancelamento por Substituição** | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| **Solicitação de Análise Fiscal** | X | X | X | V | V | V | V | V | V | V | V | V | V | X | V | V | V | V | V | V | V | V | V | V |
| **Deferido por Análise Fiscal** | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| **Indeferido por Análise Fiscal** | X | X | X | X | X | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V |
| **Confirmação do Prestador** | X | V | X | X | X | X | X/V | X/V | X | V | X/V | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| **Confirmação do Tomador** | X | V | X | X | X | X/V | X | X/V | X | X/V | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| **Confirmação do Intermediário** | X | V | X | X | X | X/V | X/V | X | X | X/V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | V |
| **Confirmação Tácita** | X | V | V | V | V | X | X | X | X | X | X | X | X | V | V | V | V | V | V | V | V | V | V | V |
| **Rejeição do Prestador** | V | V | V | V | V | V | X/V | X/V | X | X | X/V | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| **Rejeição do Tomador** | V | V | V | V | V | X/V | V | X/V | X | X/V | X | X/V | V | V | V | V | V | V | V | V | V | V | V | V |
| **Rejeição do Intermediário** | V | V | V | V | V | X/V | X/V | V | X | X/V | X/V | X | V | V | V | V | V | V | V | V | V | V | V | V |
| **Anulação Rejeição** | X | V | V | V | V | V | V | V | V | X/V | X/V | X/V | X | V | V | V | V | V | V | V | V | V | V | V |
| **Cancelamento por Ofício** | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X | X |
| **Bloqueio → e101101** | X/V | V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V | V | V | V |
| **Bloqueio → e105102** | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V | V | V |
| **Bloqueio → e105104** | V | V | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V | V |
| **Bloqueio → e105105** | V | V | V | V | X/V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | V | V |
| **Bloqueio → e305101** | V | V | V | V | V | V | V | V | V | V | V | V | V | X/V | V | V | V | V | X/V | V | V | V | V | V |
| **Desbloqueio → e101101** | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V | V | V | V |
| **Desbloqueio → e105102** | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V | V | V |
| **Desbloqueio → e105104** | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V | V |
| **Desbloqueio → e105105** | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X | V |
| **Desbloqueio → e305101** | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | V | X |

> **Bloqueio**: permitido SOMENTE para eventos que ainda não foram bloqueados ou que já foram desbloqueados.
> **Desbloqueio**: permitido SOMENTE se houver um bloqueio pendente correspondente ao identificador informado.

---

## 4. Regras de Negócio dos Campos XML

### Níveis de Regras

| Nível | Descrição |
|-------|-----------|
| 1 | Regras para consistência do leiaute |
| 2 | Regras gerais para todos os municípios aderentes ao SN NFS-e |
| 3 | Regras específicas conforme legislação municipal parametrizada no SN NFS-e |

### Colunas de Aplicabilidade

| Coluna | Descrição |
|--------|-----------|
| Emissores Públicos (Pedido enviado pelo emitente) | RNs executadas na recepção de PedRegEvt enviados pelos emitentes |
| Emissores Públicos (Decisão judicial/administrativa) | RNs executadas na geração de eventos pelos emissores públicos em condições de decisão judicial |
| ADN NFS-e (Eventos compartilhados) | RNs executadas na recepção de eventos compartilhados pelos municípios com o ADN |
| ADN NFS-e (Decisão judicial/administrativa) | RNs executadas na geração de eventos pelos emissores públicos no ADN em condição judicial |

### Tabela de Regras de Negócio

| # | Caminho | Campo | Regra | Aplicabilidade | Efeito | Código Erro | Mensagem de Erro |
|---|---------|-------|-------|----------------|--------|-------------|-----------------|
| 1 | `evento/` | `versao` | Prazo de aceitação da versão do leiaute ultrapassado | Obrig. | Rej. | E1260 | O prazo de aceitação da versão do leiaute do DF-e expirou. |
| 2 | `evento/infEvento/` | `id` | Campo identificador do Evento (EVT) inválido. Deve ser: `"EVT"` + id do PRE (56) + nSeqEvento (3) | Obrig. | Rej. | E1802 | Conteúdo do identificador informado no identificador do evento difere da concatenação dos campos correspondentes. |
| 3 | `evento/infEvento/` | `id` | O id do evento compartilhado já existe no ADN | Obrig. | Rej. | E1805 | Já existe um Evento com este identificador no ADN NFS-e. |
| 4 | `evento/infEvento/` | `id` | O id do evento gerado já existe no ADN | Obrig. | Rej. | E0802 | Já existe um documento fiscal eletrônico identificado com este id no Sistema Nacional NFS-e. |
| 5 | `evento/infEvento/` | `ambGer` | Verificar se o ambiente gerador está de acordo com a definição (1=Sistema Próprio, 2=Sefin Nacional) | Obrig. | Rej. | E1274 | O ambiente gerador da NFS-e não está de acordo com a definição. |
| 6 | `evento/infEvento/` | `dhProc` | A data/hora do registro do evento deve ser anterior à data/hora do processamento pelo Sistema Nacional | Obrig. | Rej. | E1278 | A data e hora do processamento do DF-e deve ser anterior ou igual à data da recepção pelo Sistema Nacional NFS-e. |
| 7 | `evento/pedRegEvento/` | `versao` | Prazo de aceitação da versão do leiaute do pedido ultrapassado | Obrig. | Rej. | E1825 | Prazo de aceitação da versão do leiaute do pedido de registro de evento expirou. |
| 8 | `evento/pedRegEvento/infPedReg/` | `id` | Campo identificador do PRE inválido. Deve ser: `"PRE"` + Chave NFS-e (50) + Código evento (6) | Obrig. | Rej. | E1827 | Conteúdo do identificador informado no PRE difere da concatenação dos campos correspondentes. |
| 9 | `evento/pedRegEvento/infPedReg/` | `id` | Não é permitido compartilhar com ADN os eventos de manifestação: `202201`, `203202`, `204203`, `202205`, `203206`, `204207` | Obrig. | Rej. | E1818 | Não é permitido o compartilhamento pelo município com o ADN dos eventos de manifestação de NFS-e para confirmação ou rejeição pelos não emitentes. |
| 10 | `evento/pedRegEvento/infPedReg/` | `tpAmb` | Tipo do ambiente informado difere do ambiente utilizado | Obrig. | Rej. | E1845 | Ambiente informado diverge do ambiente de recebimento para o qual o emitente está enviando o evento. |
| 11 | `evento/pedRegEvento/infPedReg/` | `dhEvento` | A data de emissão do pedido não pode ser posterior à data de recebimento do lote pelo SN NFS-e | Obrig. | Rej. | E1843 | A data de emissão do pedido do registro do evento não pode ser posterior à data de recebimento pelo Sistema Nacional NFS-e. |
| 12 | `evento/pedRegEvento/infPedReg/` | `CNPJAutor` | CNPJ do autor deve corresponder ao CNPJ base do certificado digital da assinatura | Obrig. | Rej. | E0812 | O CNPJ do autor do pedido não corresponde à base do CNPJ do certificado digital. |
| 13 | `evento/pedRegEvento/infPedReg/` | `CNPJAutor` | CNPJ deve corresponder ao autor definido na planilha "Tipo Eventos" | Obrig. | Rej. | E0813 | O CNPJ autor não corresponde ao "AUTOR DO PEDIDO DE REGISTRO DE EVENTO" indicado. |
| 14 | `evento/pedRegEvento/infPedReg/` | `CPFAutor` | CPF do autor deve corresponder ao CPF do certificado digital da assinatura | Obrig. | Rej. | E0815 | O CPF do autor não corresponde ao CPF do certificado digital. |
| 15 | `evento/pedRegEvento/infPedReg/` | `CPFAutor` | CPF deve corresponder ao autor definido na planilha "Tipo Eventos" | Obrig. | Rej. | E0816 | O CPF autor não corresponde ao "AUTOR DO PEDIDO DE REGISTRO DE EVENTO" indicado. |
| 16 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | A NFS-e indicada não existe no ADN NFS-e | Obrig. | Rej. | E1831 | O pedido de registro de evento não pode ser validado pois a NFS-e indicada não existe no ADN NFS-e. |
| 17 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | Cancelamento fora do prazo limite conforme parametrização do município emissor | Obrig. | Rej. | E0822 | O prazo para o cancelamento da NFS-e expirou, conforme parametrização do município emissor. |
| 18 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | Cancelamento acima do valor permitido pelo município emissor | Obrig. | Rej. | E0823 | Valor da NFS-e a ser cancelada acima do permitido. |
| 19 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | Cancelamento de NFS-e sem tomador identificado (conforme parametrização) | Obrig. | Rej. | E0824 | NFS-e sem identificação do tomador do serviço não pode ser cancelada. |
| 20 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | Cancelamento de NFS-e com Evento de Tributos Recolhidos vinculado (conforme parametrização) | Obrig. | Rej. | E0827 | Não é permitido cancelar NFS-e que possua Evento de Tributos Recolhidos vinculado. |
| 21 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | Pedido deve ser enviado para o ambiente que gerou a NFS-e referenciada | Obrig. | Rej. | E0831 | O pedido de registro deste evento deve ser enviado para o ambiente que gerou a NFS-e referenciada. |
| 22 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | Somente um evento de Manifestação (Confirmação e Rejeição) por não emitente da NFS-e | Obrig. | Rej. | E1833 | Somente é permitido um único evento de Manifestação (Confirmação e Rejeição), por não emitente da NFS-e. |
| 23 | `evento/pedRegEvento/infPedReg/` | `chNFSe` | Somente uma Anulação da Rejeição para cada evento de Rejeição por não emitente | Obrig. | Rej. | E1835 | Somente é permitido um único evento de Anulação da Rejeição para cada Rejeição, por não emitente. |
| 24 | `evento/pedRegEvento/infPedReg/` | `e101101` | Validar compatibilidade com eventos pré-existentes (ver aba RN EVENTOSxEVENTOS) | Obrig. | Rej. | E0840 | O SN NFS-e não pode recepcionar o EVENTO DE CANCELAMENTO DE NFS-e pois um evento pré-existente impede sua recepção. |
| 25 | `evento/pedRegEvento/infPedReg/` | `e105102` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0845 | O SN NFS-e não pode recepcionar o EVENTO DE CANCELAMENTO DE NFS-e POR SUBSTITUIÇÃO. |
| 26 | `evento/pedRegEvento/infPedReg/` | `e101103` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0848 | O SN NFS-e não pode recepcionar o EVENTO DE SOLICITAÇÃO DE ANÁLISE FISCAL. |
| 27 | `evento/pedRegEvento/infPedReg/` | `e105104` | Deve existir um evento de Solicitação de Análise Fiscal pendente | Obrig. | Rej. | E0853 | Não existe um Evento de Solicitação de Análise Fiscal pendente para deferimento. |
| 28 | `evento/pedRegEvento/infPedReg/` | `e105104` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0852 | O SN NFS-e não pode recepcionar o EVENTO DE CANCELAMENTO DE NFS-e DEFERIDO POR ANÁLISE FISCAL. |
| 29 | `evento/pedRegEvento/infPedReg/` | `e105105` | Deve existir um evento de Solicitação de Análise Fiscal pendente | Obrig. | Rej. | E0856 | Não existe um Evento de Solicitação de Análise Fiscal pendente para indeferimento. |
| 30 | `evento/pedRegEvento/infPedReg/` | `e105105` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0855 | O SN NFS-e não pode recepcionar o EVENTO DE CANCELAMENTO DE NFS-e INDEFERIDO POR ANÁLISE FISCAL. |
| 31 | `evento/pedRegEvento/infPedReg/` | `e202201` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0860 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — CONFIRMAÇÃO DO PRESTADOR. |
| 32 | `evento/pedRegEvento/infPedReg/` | `e203202` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0861 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — CONFIRMAÇÃO DO TOMADOR. |
| 33 | `evento/pedRegEvento/infPedReg/` | `e204203` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0862 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — CONFIRMAÇÃO DO INTERMEDIÁRIO. |
| 34 | `evento/pedRegEvento/infPedReg/` | `e205204` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0863 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — CONFIRMAÇÃO TÁCITA. |
| 35 | `evento/pedRegEvento/infPedReg/` | `e202205` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0864 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — REJEIÇÃO DO PRESTADOR. |
| 36 | `evento/pedRegEvento/infPedReg/e202205/` | `xMotivo` | Obrigatório quando `cMotivo=9` (Outros) | Obrig. | Rej. | E1944 | A descrição do motivo é obrigatória caso o tipo do motivo seja "9 - Outros". |
| 37 | `evento/pedRegEvento/infPedReg/` | `e203206` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0866 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — REJEIÇÃO DO TOMADOR. |
| 38 | `evento/pedRegEvento/infPedReg/e203206/` | `xMotivo` | Obrigatório quando `cMotivo=9` (Outros) | Obrig. | Rej. | E1949 | A descrição do motivo é obrigatória caso o tipo do motivo seja "9 - Outros". |
| 39 | `evento/pedRegEvento/infPedReg/` | `e204207` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0868 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — REJEIÇÃO DO INTERMEDIÁRIO. |
| 40 | `evento/pedRegEvento/infPedReg/e204207/` | `xMotivo` | Obrigatório quando `cMotivo=9` (Outros) | Obrig. | Rej. | E1954 | A descrição do motivo é obrigatória caso o tipo do motivo seja "9 - Outros". |
| 41 | `evento/pedRegEvento/infPedReg/` | `e205208` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E0870 | O SN NFS-e não pode recepcionar o EVENTO DE MANIFESTAÇÃO — ANULAÇÃO DA REJEIÇÃO. |
| 42 | `evento/pedRegEvento/infPedReg/e205208/` | `idEvManifRej` | O id de rejeição deve existir no SN NFS-e e estar vinculado à NFS-e do evento | Obrig. | Rej. | E1963 | O identificador do Evento de Rejeição a ser anulado deve existir e corresponder à NFS-e informada. |
| 43 | `evento/pedRegEvento/infPedReg/` | `e305101` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E1960 | O SN NFS-e não pode recepcionar o EVENTO DE CANCELAMENTO DE NFS-e POR OFÍCIO. |
| 44 | `evento/pedRegEvento/infPedReg/` | `e305102` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E1965 | O SN NFS-e não pode recepcionar o EVENTO DE BLOQUEIO DE NFS-e POR OFÍCIO. |
| 45 | `evento/pedRegEvento/infPedReg/e305102/` | `codEvento` | O evento de bloqueio deve ser rejeitado se já houver bloqueio do mesmo tipo sem desbloqueio correspondente | Obrig. | Rej. | E1967 | Já existe o mesmo tipo de evento de bloqueio vinculado à NFS-e sem o Evento de Desbloqueio correspondente. |
| 46 | `evento/pedRegEvento/infPedReg/` | `e305103` | Validar compatibilidade com eventos pré-existentes | Obrig. | Rej. | E1970 | O SN NFS-e não pode recepcionar o EVENTO DE DESBLOQUEIO DE NFS-e POR OFÍCIO. |
| 47 | `evento/pedRegEvento/infPedReg/e305103/` | `idBloqOfic` | O id do bloqueio indicado deve existir no SN NFS-e | Obrig. | Rej. | E1976 | Não existe o identificador de bloqueio informado neste evento para desbloqueio. |
| 48 | `evento/pedRegEvento/infPedReg/e305103/` | `idBloqOfic` | O id do bloqueio indicado não pode já ter sido desbloqueado | Obrig. | Rej. | E1978 | O Evento de Bloqueio indicado já foi desbloqueado. |

#### Regras de Assinatura Digital

| # | Caminho | Campo | Regra | Cod. Erro | Mensagem | Emissores Públicos | ADN |
|---|---------|-------|-------|-----------|----------|--------------------|-----|
| 49 | `evento/pedRegEvento/` | `Signature` | Assinatura do PRE deve ser válida | E1980 | Arquivo enviado com erro na assinatura. | V | X |
| 50 | `evento/pedRegEvento/` | `Signature` | Certificado Digital inválido (validade, cadeia, revogação, LCR) | E1983 | Certificado Digital da assinatura inválido. | V | X |
| 51 | `evento/pedRegEvento/` | `Signature` | Certificado fora do padrão NFS-e (versão, BC, KeyUsage, CNPJ/CPF OID, raiz ICP-Brasil) | E1986 | Certificado Digital fora do padrão estabelecido. | V | X |
| 52 | `evento/pedRegEvento/` | `Signature` | Assinatura obrigatória ao enviar para Web Service | E1989 | A assinatura é obrigatória quando enviado para o Web Service. | V | X |
| 53 | `evento/pedRegEvento/` | `Signature` | Assinatura deve ser feita com o certificado digital do emitente do PRE | E1991 | A assinatura deve ser feita com o certificado digital do emitente do PRE. | V | X |
| 54 | `evento/` | `Signature` | Assinatura do Evento deve ser válida | E2020 | Arquivo enviado com erro na assinatura. | X | V |
| 55 | `evento/` | `Signature` | Certificado Digital inválido (validade, cadeia, revogação, LCR) | E2023 | Certificado Digital da assinatura inválido. | X | V |
| 56 | `evento/` | `Signature` | Certificado fora do padrão NFS-e | E2026 | Certificado Digital fora do padrão estabelecido. | X | V |
| 57 | `evento/` | `Signature` | Assinatura obrigatória ao enviar para API | E2029 | A assinatura é obrigatória quando enviado para a API. | X | V |
| 58 | `evento/` | `Signature` | Assinatura deve ser feita com o certificado digital do município emissor | E2032 | A assinatura deve ser feita com o certificado digital do município emissor. | X | V |

---

## 5. Legendas e Glossário

| Sigla / Termo | Descrição |
|---------------|-----------|
| ADN | Ambiente de Dados Nacional |
| SN NFS-e | Sistema Nacional NFS-e |
| PedRegEvt / PedRegEvento | Pedido de Registro de Evento |
| PRE | Identificador do Pedido de Registro de Evento (`"PRE"` + 56 dígitos) |
| EVT | Identificador do Evento (`"EVT"` + 59 dígitos) |
| DFSe | Documento Fiscal de Serviços Eletrônico |
| chNFSe | Chave de acesso da NFS-e (50 caracteres numéricos) |
| ISSQN | Imposto Sobre Serviços de Qualquer Natureza |
| ICP-Brasil | Infraestrutura de Chaves Públicas Brasileira |
| LCR | Lista de Certificados Revogados |
| UTC | Tempo Universal Coordenado |
| TZD | Time Zone Designator (ex.: `-03:00` para Brasília) |
| Obrig. | Aplicabilidade Obrigatória |
| Rej. | Efeito Rejeição |
| V | Executado / Permitido |
| X | Não executado / Não Permitido |
| X/V | Depende de condição específica |
| ELE | Tipo de elemento XML (E=Elemento, G=Grupo, A=Atributo, ID=Identificador, CE=Choice Element, CG=Choice Group) |
| TIPO | Tipo de dado (C=Alfanumérico, N=Numérico, D=Data/Domínio) |
| OCOR. | Ocorrência (ex.: `1-1`=obrigatório único, `0-1`=opcional) |
| TAM. | Tamanho do campo (ex.: `1-20`=mín. 1, máx. 20 caracteres) |
