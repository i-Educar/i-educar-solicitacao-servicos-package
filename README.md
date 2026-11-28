# Solicitação de serviços

Chamados e solicitações da rede escolar: fluxos, anexos, SLAs e relatórios — base também para NMS.

Composer: `buriti/i-educar-solicitacao-servicos-package`

## Para que serve

Organiza pedidos de serviço entre escolas e SME (manutenção, TI, logística etc.) com status, anexos, prazos e visão gerencial.

## Desenho de funções

| Função | Entrega |
| --- | --- |
| Abertura de chamado | Solicitação categorizada pela escola/SME |
| Fluxos e status | Trâmite até conclusão |
| Anexos | Evidências e documentos do chamado |
| SLA | Prazos e indicadores de atendimento |
| Relatórios | Volume, atrasos e desempenho |

## Quem usa

- Escolas (solicitantes)
- Equipes executoras SME
- Gestores (SLA)

## Integrações

- Núcleo (escolas/usuários)
- Assinatura digital (quando o fluxo exigir)
- Exportação

## Compatibilidade

| Produto | Versão |
| --- | --- |
| **i-Educar** | **2.12.x** |
| **i-Diário** | Portabilis i-Diário (API / SSO do núcleo) |
| Pacote | `0.2.1` |

Declarado em `composer.json` → `extra.ieducar` / `extra.idiario`.

## Documentação

| Documento | Uso |
| --- | --- |
| [`EPIC.md`](./EPIC.md) | Escopo de produto, aceite e critérios |
| [`ROADMAP.md`](./ROADMAP.md) | Fases e entregas |
| [`TODO.md`](./TODO.md) | Backlog operacional |
| [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) | Rastreio de requisitos (planejamento) |

---

Licença: **GPL-2.0-or-later** · Organização [i-Educar](https://github.com/i-Educar)
