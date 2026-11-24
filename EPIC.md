# EPIC — Solicitação de Serviços (escola ↔ SME)

| Campo | Valor |
| --- | --- |
| Pacote | `buriti/i-educar-solicitacao-servicos-package` |
| TR Laguna | **5.6** |
| Prioridade | **P1** |
| Nota atual (executivo) | **C** |
| Fonte | `tmp/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md` |
| Regra de ouro | Pregão Eletrônico 77/2026 · Laguna/SC · PoC ≥ 85% por módulo · gaps ≤ 30 dias |
| Base atual | serventec/i-educar-chamados-package (parcial) |

## Objetivo

Alinhar chamados a fluxos, anexos, SLAs e relatórios do edital Laguna; base também para NMS.

## Padrão técnico (i-Educar 2.12)

| Item | Valor |
| --- | --- |
| Alvo | i-Educar **2.12** · PHP **≥ 8.3** · Illuminate **^11|^12|^13** (Laravel 13 no app) |
| Composer | `buriti/i-educar-solicitacao-servicos-package` |
| Namespace | `iEducar\Packages\BuritiSolicitacaoServicos\` → `src/` |
| Compat | `extra.ieducar.compatible: ["2.12"]` · GPL-2.0-or-later · `type: library` |
| Schema / dados | solicitacao_servicos **ou** extensão/envelope de `serventec/i-educar-chamados-package` |
| Rotas | prefixo `solicitacao-servicos`; SME com middleware `ieducar.*` + `auth`; API apps via Sanctum quando exposto |
| Tenancy | `PackageTenant::allows('buriti/i-educar-solicitacao-servicos-package')` antes de migrations/menu |
| Plug-and-play | path em `packages/buriti/…`; manter em `ignore` até instalável |
| Referência | `packages/buriti/PADRAO-PACOTE-2.12.md` |

**Nota de arquitetura:** Decisão F0: envelopar chamados Serventec vs pacote Buriti paralelo. Alvo PoC 2.12.

## Cobertura 100% do TR

Este EPIC cobre **todos** os requisitos do módulo(s) 5.6 listados em [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) (14 itens OCR).

- PoC: ≥ **85%** demonstráveis e operacionais.
- Até **15%** restante: produção em ≤ **30 dias**, sem ônus.
- Nenhum item do `REQUISITOS-TR.md` pode ficar fora do `ROADMAP.md` / `TODO.md` (direto ou por grupo rastreável).

## Épicos filhos

1. Tipos de solicitação e fluxos escola → SME.
2. Anexos, prazos/SLA e glosas operacionais.
3. Painéis e relatórios exigidos no TR.
4. Instrumentação de indisponibilidade/NMS quando couber.

## Critérios de aceite (PoC)

- [ ] Checklist [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) com **100%** dos itens classificados (atende / gap / N/A)
- [ ] Itens **atende** demonstráveis na PoC (≥ 85% do módulo 5.6)
- [ ] Itens **gap** ≤ 15% com plano ≤ 30 dias em produção, sem ônus
- [ ] Nenhum requisito do TR omitido da matriz


## Fora de escopo deste EPIC

- Orçamento comercial da proposta Laguna.
- Substituição do core i-Educar fora do domínio do módulo.
