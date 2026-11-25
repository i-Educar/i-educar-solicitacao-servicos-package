# Solicitação de serviços (TR 5.6)

Pacote Buriti para o i-Educar **2.12** (organização [i-Educar](https://github.com/i-Educar)).

Composer: `buriti/i-educar-solicitacao-servicos-package`

## Compatibilidade

| Produto | Versão |
| --- | --- |
| **i-Educar** | **2.12.x** (min. 2.12.0) |
| **i-Diário** | Portabilis i-Diário (espelho Laguna) via API/SSO do core |
| Pacote | `0.2.0` |

Declarado em `composer.json` → `extra.ieducar` e `extra.idiario`.

## Documentos Laguna / PE-77/2026

- `EPIC.md` · `ROADMAP.md` · `TODO.md` · `REQUISITOS-TR.md`
- Padrão: `packages/buriti/PADRAO-PACOTE-2.12.md` (no monorepo)

## Status

Scaffold inicial (documentação + ServiceProvider). Implementação segue a ordem em `ORDEM-DESENVOLVIMENTO-LAGUNA.md`.

## Onda 2

```bash
php packages/buriti/i-educar-solicitacao-servicos-package/tests/run.php
```
