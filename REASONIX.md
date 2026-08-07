# REASONIX.md — NFS-e Nacional WHMCS Module

> **Regras completas em `AGENTS.md`** — este arquivo contém apenas instruções
> específicas para o Reasonix Code. Leia `AGENTS.md` primeiro para entender
> arquitetura, namespaces, e fluxo de desenvolvimento (SDD).

## Comandos do projeto

```bash
# Setup inicial (uma vez após clonar)
cd nfsenacional-WHMCS && composer install

# Testes (quando houver)
cd nfsenacional-WHMCS && composer test

# Rodar um script one-shot
php -f nfsenacional-WHMCS/script.php
```

## Arquivos de referência — leia antes de propor mudanças

| Arquivo | Quando ler |
| --- | --- |
| `AGENTS.md` | **Sempre.** Regras de arquitetura, estilo, proibições. |
| `DOCUMENTACAO-REFERENCIA-NFSE.md` | Dúvidas sobre códigos IBGE, NBS, tributação, schemas XSD, endpoints de API. |
| `nfsenacional-WHMCS/CHANGELOG.md` | Antes de criar uma entrada de changelog para nova versão. |
| `MANUAL API/v1-API-NFS-e-*.json` | Implementar ou depurar chamadas à API da Sefin Nacional. |
| `esquemas-nfse-rtc-v1-01-*/tiposComplexos_v1.01.xsd` | Montar/altear XML da DPS ou NFS-e. |
| `esquemas-nfse-rtc-v1-01-*/tiposSimples_v1.01.xsd` | Validar formatos de campos (regex, tamanho máximo). |
| `esquemas-nfse-rtc-v1-01-*/tiposEventos_v1.01.xsd` | Implementar cancelamento ou outros eventos. |

## Regras Reasonix-específicas

### Antes de afirmar que algo não existe

Use `search_content` para confirmar. Exemplo:

```
❌ "O módulo não tem testes."   ← afirmação sem evidência
✅ "Nenhum arquivo *Test*.php encontrado (search_files '*Test*.php' em src/)."
```

### Antes de editar um arquivo

Use `read_file` no arquivo alvo **nesta sessão**. O editor bloqueia edições em
arquivos não lidos.

### Ao propor refactors

- Verifique `search_content` em TODOS os call sites da função/classe que pretende alterar
- `AmbienteGuard` é intocável — qualquer mudança afeta produção e homologação simultaneamente
- Mudanças em `Domain/` exigem testes novos obrigatoriamente (SDD)

### Ao adicionar dependências

```bash
cd nfsenacional-WHMCS && composer require vendor/package
```

Sempre commit o `composer.lock` atualizado. NUNCA commit `vendor/`.

## Fluxo de trabalho com este agente

1. **Explorar:** Peça "analise X" ou "como funciona Y" — usarei `search_content`,
   `read_file`, e `get_symbols` para entender o código antes de responder.

2. **Especificar:** Peça "crie a spec para feature Z" — gerarei `spec/Z.md`
   seguindo o template do AGENTS.md.

3. **Implementar:** Após spec aprovada, peça "implemente a feature Z" —
   seguirei SDD: testes → código → verificação.

4. **Release:** Basta bump na versão + changelog + merge na main. O workflow
   `.github/workflows/release.yml` faz o resto automaticamente.
