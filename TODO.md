# TODO — Solicitação de Serviços (escola ↔ SME)

> Pacote: `buriti/i-educar-solicitacao-servicos-package` · TR **5.6** · Prioridade **P1**


## Onda 2 (código inicial 2026-11-24)

- [x] Services de domínio + testes
- [x] docs/onda-2/
- [ ] Migrations/UI completas

## Backlog Laguna

- [ ] Checklist 5.6.x
- [ ] Estender ou envelopar chamados-package
- [ ] Anexos e tipos
- [ ] SLA configurável
- [ ] Relatórios
- [ ] Hooks NMS (P2 ops)
- [ ] Roteiro PoC

## Scaffold 2.12 (quando iniciar código)

- [ ] `composer.json` (`buriti/…`, PHP ≥8.3, illuminate/support, `compatible: ["2.12"]`)
- [ ] `ServiceProvider` + `const PACKAGE`
- [ ] `routes/web.php` com middleware i-Educar
- [ ] Migrations no schema do domínio + guard `PackageTenant`
- [ ] Processos/menu (faixa própria)
- [ ] README + INSTALL espelhando transporte/setup
- [ ] Entrada futura em `packages/plug-and-play.json` (fora do ignore)
- [ ] Revisar `packages/buriti/PADRAO-PACOTE-2.12.md`

## Cobertura 100% do TR

- [ ] Percorrer 100% de [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) (14 itens) e marcar atende/gap/N/A

- [ ] Abrir tarefas para **cada gap** (ou agrupar com IDs explícitos)

- [ ] Evidências de PoC por item atende

- [ ] Lista dos ≤15% com datas ≤30 dias

### Lotes (mesmo do ROADMAP)

- [ ] Lote R1: 5.6.1 … 5.6.15

## Gate PoC

- [ ] `REQUISITOS-TR.md` 100% classificado
- [ ] ≥ 85% atende com evidência
- [ ] ≤ 15% gap com plano ≤ 30 dias
- [ ] Roteiro binário ensaiado

## Referências

- `tmp/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md`
- OCR: `/tmp/edital-ocr/p-01.txt` … `p-79.txt` (quando disponível)
