# ROADMAP — Solicitação de Serviços (escola ↔ SME)

> Pacote: `buriti/i-educar-solicitacao-servicos-package` · TR **5.6** · Prioridade **P1** · Nota **C**  
> Alinhado a `packages/buriti/docs/laguna/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md`

## Visão

Alinhar chamados a fluxos, anexos, SLAs e relatórios do edital Laguna; base também para NMS.

## Fases

### F0 · Gap chamados

Mapear package atual × 5.6.x.

### F1 · Fluxos Laguna

Tipos, status, anexos.

### F2 · SLA

Prazos e indicadores.

### F3 · Relatórios

Exportações do módulo.

### F4 · PoC + NMS

Demo e relatório mensal de chamados.

## Cobertura 100% dos requisitos (TR)

Fonte: [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) — **14 itens**.

Cada lote deve sair da PoC como **atende** ou entrar no plano de 30 dias.

### Lote R1 — itens 5.6.1 … 5.6.15 (14)

- `5.6.1`, `5.6.2`, `5.6.3`, `5.6.4`, `5.6.5`, `5.6.6`, `5.6.7`, `5.6.8`, `5.6.9`, `5.6.10`, `5.6.12`, `5.6.13`, `5.6.14`, `5.6.15`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Gate 100%

- [ ] Todos os lotes R* com status atende ou gap≤30dias
- [ ] Soma atende ≥ 85% na demonstração

## Conformidade Laravel / pacote i-Educar 2.12

Antes da fase de código:

1. Criar `composer.json` com `php >=8.3`, `illuminate/support`, provider em `extra.laravel`, `compatible: ["2.12"]`.
2. Scaffold: `src/Providers`, `Http/Controllers`, `Services`, `Support`, `database/migrations`, `routes/web.php`, views Blade.
3. Schema `solicitacao_servicos **ou** extensão/envelope de `serventec/i-educar-chamados-package`` (ou estratégia documentada); FKs lógicas para núcleo (`ref_cod_aluno`, `ref_cod_escola`, etc.).
4. Menu/processos em migration própria; respeitar multi-tenant.
5. Não acoplar UI mobile; dados para lojas só via ``buriti/i-educar-apps-api-package``.
6. Seguir `packages/buriti/PADRAO-PACOTE-2.12.md`.

## Dependências

- Cadastro mestre de aluno/escola/turma no i-Educar (SGE).
- Matriz item a item OCR do Anexo I antes de cravar %.
- Alinhamento com `i-educar-apps-api-package` (APIs 5.13/5.14; UI/lojas em equipe externa) e exports (4.x) quando o módulo expõe dados ao cidadão/SME.

## Riscos

- Valor global do edital incompatível com greenfield total — reuso e MVP demonstrável são obrigatórios.
- PDF escaneado: numerações OCR podem exigir reconciliação manual.
- PoC só conta o que estiver operacional no dia.

## Definição de pronto da fase PoC

Checklist do `TODO.md` marcado para a fatia MVP + roteiro de demonstração ensaiado.
