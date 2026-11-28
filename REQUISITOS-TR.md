# Requisitos TR 5.6 — Solicitação de serviços

**Edital:** PE 77/2026 Laguna/SC · Anexo I
**Cobertura documental:** **100%** dos 14 itens OCR abaixo.
**PoC:** ≥85% demonstráveis; ≤15% em ≤30 dias.
**Matriz mestre:** `packages/buriti/docs/laguna/MATRIZ-REQUISITOS-TR.md`

## 5.6 — Solicitação de serviços (14 itens)

### Checklist 100%

- [ ] **5.6.1** — Registro de manutenção possibilitando identificar qual profissional fará o trabalho em
- [ ] **5.6.2** — Registro de chamados, constando de data de abertura, escola de origem, solicitante,
- [ ] **5.6.3** — Emissão de relatório para conhecimento do andamento dos chamados;
- [ ] **5.6.4** — Emissão do chamado de forma sintética e detalhada;
- [x] **5.6.5** — Emissão da situação dos chamados por período; **[Onda 6: filtros data_inicio/fim + CSV + print em relatorios]**
- [x] **5.6.6** — Possibilidade de restringir a visualização e manutenção dos chamados por categoria de **[Onda 6: config restricoes_categoria + validação em salvarChamado]**
- [x] **5.6.7** — Aviso automático da ocorrência de novos chamados; **[Onda 6: Notificador ao encaminhar/abrir]**
- [x] **5.6.8** — Restringir a abertura de novos chamados, caso exista outros chamados abertos conforme **[Onda 6: config limite_chamados + validação]**
- [ ] **5.6.9** — Como forma de facilitar e agilizar as atividades, os recursos abaixo devem estar
- [ ] **5.6.10** — As informações geradas devem estar disponíveis em tempo real no sistema;
- [ ] **5.6.12** — Permitir a consulta de solicitações, por período, categoria, estabelecimento, situação,
- [ ] **5.6.13** — Permitir o registro do andamento dos chamados, cadastrando pareceres curtos sobre o
- [ ] **5.6.14** — Permitir anexar documentos e fotos (inclusive edição) aos chamados;
- [x] **5.6.15** — Permitir notificar o técnico sobre a ocorrência de chamados. **[Onda 6: Notificador.paraUsuario em encaminhar]**

## Regra de rastreio

- Todo item coberto por fase no `ROADMAP.md` e tarefa/grupo no `TODO.md`.
- `EPIC.md` declara cobertura 100% via este arquivo.
- Reconciliar IDs com ruído OCR no PDF antes da PoC.
