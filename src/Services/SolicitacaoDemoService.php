<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persistência de chamados em todas as etapas do FluxoChamado, com atores distintos.
 */
final class SolicitacaoDemoService
{
    public const DEMO_TAG = 'laguna-carga-v1';

    /**
     * @return array{categorias:int,setores:int,chamados:int,andamentos:int,pulado:bool,mensagem:string}
     */
    public function executar(bool $force = false): array
    {
        $vazio = [
            'categorias' => 0,
            'setores' => 0,
            'chamados' => 0,
            'andamentos' => 0,
            'pulado' => false,
            'mensagem' => '',
        ];

        if (! PocRegistro::disponivel()) {
            $vazio['mensagem'] = 'schema solicitacao_servicos ausente';

            return $vazio;
        }

        if (! $force && $this->jaPovoado()) {
            $vazio['pulado'] = true;
            $vazio['mensagem'] = 'já povoado (use --force)';

            return $vazio;
        }

        if ($force) {
            $this->limparDemo();
        }

        $categorias = $this->garantirCategorias();
        $setores = $this->garantirSetores();
        $carga = $this->semearChamados();

        return [
            'categorias' => $categorias,
            'setores' => $setores,
            'chamados' => $carga['chamados'],
            'andamentos' => $carga['andamentos'],
            'pulado' => false,
            'mensagem' => sprintf(
                'categorias=%d setores=%d chamados=%d andamentos=%d',
                $categorias,
                $setores,
                $carga['chamados'],
                $carga['andamentos'],
            ),
        ];
    }

    private function jaPovoado(): bool
    {
        foreach (PocRegistro::listar('chamado', 50) as $c) {
            if (($c['payload']['demo'] ?? null) === self::DEMO_TAG) {
                return true;
            }
        }

        return false;
    }

    private function limparDemo(): void
    {
        if (! Schema::hasTable(PocRegistro::TABELA)) {
            return;
        }
        $ids = [];
        foreach (PocRegistro::listar('chamado', 500) as $c) {
            if (($c['payload']['demo'] ?? null) === self::DEMO_TAG) {
                $ids[] = $c['id'];
            }
        }
        if ($ids === []) {
            return;
        }
        foreach (['mensagem', 'andamento', 'auditoria'] as $tipo) {
            foreach (PocRegistro::listar($tipo, 1000) as $r) {
                $cid = (int) ($r['payload']['chamado_id'] ?? 0);
                if (in_array($cid, $ids, true) || ($r['payload']['demo'] ?? null) === self::DEMO_TAG) {
                    DB::table(PocRegistro::TABELA)->where('id', $r['id'])->delete();
                }
            }
        }
        DB::table(PocRegistro::TABELA)->whereIn('id', $ids)->delete();
    }

    private function garantirCategorias(): int
    {
        if (PocRegistro::contar('categoria') > 0) {
            return 0;
        }
        $n = 0;
        foreach ([
            ['Manutenção predial', 48],
            ['TI / conectividade', 24],
            ['Material didático', 72],
            ['Transporte / logística', 36],
        ] as [$nome, $sla]) {
            PocRegistro::gravar('categoria', $nome, $sla.'h', [
                'nome' => $nome,
                'sla_horas' => $sla,
                'ativo' => true,
                'demo' => self::DEMO_TAG,
            ]);
            $n++;
        }

        return $n;
    }

    private function garantirSetores(): int
    {
        if (count(SetorChamado::listar(false)) > 0) {
            return 0;
        }
        $n = 0;
        foreach ([
            ['Manutenção predial', 'Obras e reparos nas escolas', [['nome' => 'Gestor Chamados SME', 'user_id' => 0]]],
            ['Administrativo', 'Demandas administrativas SME↔escola', [['nome' => 'Secretária Municipal Educação', 'user_id' => 0]]],
            ['TI Educacional', 'Infraestrutura de rede e dispositivos', [['nome' => 'Gestor Chamados SME', 'user_id' => 0]]],
        ] as [$nome, $desc, $resp]) {
            SetorChamado::gravar($nome, $desc, $resp, true);
            $n++;
        }

        return $n;
    }

    /**
     * @return array{chamados:int,andamentos:int}
     */
    private function semearChamados(): array
    {
        $escolas = $this->escolas();
        $setores = SetorChamado::listar(true);
        $setorManut = $setores[0]['nome'] ?? 'Manutenção predial';
        $setorAdm = $setores[1]['nome'] ?? 'Administrativo';

        $cenarios = [
            [
                'assunto' => 'Quadro elétrico com aquecimento',
                'prioridade' => 'alta',
                'categoria' => 'manutencao',
                'situacao' => FluxoChamado::STATUS_ABERTO,
                'setor' => $setorManut,
                'solicitante' => 'Diretor EMEF Anita',
                'responsavel' => null,
                'passos' => [
                    ['autor' => 'Diretor EMEF Anita', 'texto' => 'Chamado aberto pela escola', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                ],
            ],
            [
                'assunto' => 'Troca de lâmpadas sala 12',
                'prioridade' => 'media',
                'categoria' => 'manutencao',
                'situacao' => FluxoChamado::STATUS_TRIAGEM,
                'setor' => $setorManut,
                'solicitante' => 'Diretor Polo Norte',
                'responsavel' => 'Gestor Chamados SME',
                'passos' => [
                    ['autor' => 'Diretor Polo Norte', 'texto' => 'Chamado aberto', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                    ['autor' => 'Gestor Chamados SME', 'texto' => 'Em triagem — verificar estoque', 'de' => FluxoChamado::STATUS_ABERTO, 'para' => FluxoChamado::STATUS_TRIAGEM],
                ],
            ],
            [
                'assunto' => 'Vazamento sanitário bloco B',
                'prioridade' => 'alta',
                'categoria' => 'manutencao',
                'situacao' => FluxoChamado::STATUS_ATENDIMENTO,
                'setor' => $setorManut,
                'solicitante' => 'Coordenadora Pedagógica',
                'responsavel' => 'Equipe Manutenção SME',
                'passos' => [
                    ['autor' => 'Coordenadora Pedagógica', 'texto' => 'Chamado aberto', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                    ['autor' => 'Gestor Chamados SME', 'texto' => 'Triagem concluída', 'de' => FluxoChamado::STATUS_ABERTO, 'para' => FluxoChamado::STATUS_TRIAGEM],
                    ['autor' => 'Equipe Manutenção SME', 'texto' => 'Equipe deslocada à escola', 'de' => FluxoChamado::STATUS_TRIAGEM, 'para' => FluxoChamado::STATUS_ATENDIMENTO],
                ],
            ],
            [
                'assunto' => 'Solicitação de notebooks para lab',
                'prioridade' => 'media',
                'categoria' => 'ti',
                'situacao' => FluxoChamado::STATUS_AGUARDANDO,
                'setor' => $setorAdm,
                'solicitante' => 'Organizador Horários',
                'responsavel' => 'Secretária Municipal Educação',
                'passos' => [
                    ['autor' => 'Organizador Horários', 'texto' => 'Chamado aberto', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                    ['autor' => 'Secretária Municipal Educação', 'texto' => 'Aguardando orçamento da escola', 'de' => FluxoChamado::STATUS_ABERTO, 'para' => FluxoChamado::STATUS_AGUARDANDO],
                ],
            ],
            [
                'assunto' => 'Pintura de muro externo',
                'prioridade' => 'baixa',
                'categoria' => 'geral',
                'situacao' => FluxoChamado::STATUS_RESOLVIDO,
                'setor' => $setorManut,
                'solicitante' => 'Diretor EMEF Anita',
                'responsavel' => 'Equipe Manutenção SME',
                'feedback' => ['nota' => 5, 'comentario' => 'Serviço concluído no prazo'],
                'passos' => [
                    ['autor' => 'Diretor EMEF Anita', 'texto' => 'Chamado aberto', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                    ['autor' => 'Gestor Chamados SME', 'texto' => 'Em atendimento', 'de' => FluxoChamado::STATUS_ABERTO, 'para' => FluxoChamado::STATUS_ATENDIMENTO],
                    ['autor' => 'Equipe Manutenção SME', 'texto' => 'Serviço concluído', 'de' => FluxoChamado::STATUS_ATENDIMENTO, 'para' => FluxoChamado::STATUS_RESOLVIDO],
                ],
            ],
            [
                'assunto' => 'Reparo de portão — fechado com feedback',
                'prioridade' => 'media',
                'categoria' => 'manutencao',
                'situacao' => FluxoChamado::STATUS_FECHADO,
                'setor' => $setorManut,
                'solicitante' => 'Diretor Polo Norte',
                'responsavel' => 'Gestor Chamados SME',
                'feedback' => ['nota' => 4, 'comentario' => 'Ok, demorou um pouco'],
                'passos' => [
                    ['autor' => 'Diretor Polo Norte', 'texto' => 'Chamado aberto', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                    ['autor' => 'Gestor Chamados SME', 'texto' => 'Resolvido', 'de' => FluxoChamado::STATUS_ABERTO, 'para' => FluxoChamado::STATUS_RESOLVIDO],
                    ['autor' => 'Diretor Polo Norte', 'texto' => 'Fechado após feedback', 'de' => FluxoChamado::STATUS_RESOLVIDO, 'para' => FluxoChamado::STATUS_FECHADO],
                ],
            ],
            [
                'assunto' => 'Pedido fora do escopo SME',
                'prioridade' => 'baixa',
                'categoria' => 'geral',
                'situacao' => FluxoChamado::STATUS_INDEFERIDO,
                'setor' => $setorAdm,
                'solicitante' => 'Professor Rede',
                'responsavel' => 'Secretária Municipal Educação',
                'passos' => [
                    ['autor' => 'Professor Rede', 'texto' => 'Chamado aberto', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                    ['autor' => 'Secretária Municipal Educação', 'texto' => 'Indeferido — competência de outra secretaria', 'de' => FluxoChamado::STATUS_ABERTO, 'para' => FluxoChamado::STATUS_INDEFERIDO],
                ],
            ],
            [
                'assunto' => 'Solicitação duplicada cancelada',
                'prioridade' => 'baixa',
                'categoria' => 'geral',
                'situacao' => FluxoChamado::STATUS_CANCELADO,
                'setor' => $setorAdm,
                'solicitante' => 'Diretor EMEF Anita',
                'responsavel' => null,
                'passos' => [
                    ['autor' => 'Diretor EMEF Anita', 'texto' => 'Chamado aberto', 'de' => null, 'para' => FluxoChamado::STATUS_ABERTO],
                    ['autor' => 'Diretor EMEF Anita', 'texto' => 'Cancelado pela escola (duplicidade)', 'de' => FluxoChamado::STATUS_ABERTO, 'para' => FluxoChamado::STATUS_CANCELADO],
                ],
            ],
        ];

        $chamados = 0;
        $andamentos = 0;
        foreach ($cenarios as $i => $c) {
            $escola = $escolas[$i % count($escolas)];
            $abertoEm = now()->subDays(8 - min(7, $i))->toIso8601String();
            $id = PocRegistro::gravar('chamado', $c['assunto'], $escola.' · '.$c['prioridade'], [
                'escola' => $escola,
                'assunto' => $c['assunto'],
                'prioridade' => $c['prioridade'],
                'categoria' => $c['categoria'],
                'descricao' => 'Cenário demo multi-etapa · '.$c['situacao'],
                'situacao' => $c['situacao'],
                'aberto_em' => $abertoEm,
                'resolvido_em' => in_array($c['situacao'], [FluxoChamado::STATUS_RESOLVIDO, FluxoChamado::STATUS_FECHADO], true)
                    ? now()->subDays(1)->toIso8601String()
                    : null,
                'setor' => $c['setor'],
                'responsavel' => $c['responsavel'],
                'solicitante' => $c['solicitante'],
                'anexo_ids' => [],
                'feedback' => $c['feedback'] ?? null,
                'demo' => self::DEMO_TAG,
            ]);
            $chamados++;
            foreach ($c['passos'] as $passo) {
                TimelineChamado::andamento(
                    $id,
                    $passo['texto'],
                    $passo['autor'],
                    $passo['de'],
                    $passo['para'],
                    $c['setor'],
                    $c['responsavel'],
                );
                $andamentos++;
            }
        }

        return compact('chamados', 'andamentos');
    }

    /** @return list<string> */
    private function escolas(): array
    {
        try {
            if (! Schema::hasTable('pmieducar.escola')) {
                return ['EMEF Norte', 'EMEF Sul'];
            }
            $rows = DB::table('pmieducar.escola as e')
                ->join('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->where('e.ativo', 1)
                ->orderBy('j.fantasia')
                ->limit(8)
                ->pluck('j.fantasia');
            $nomes = $rows->map(fn ($n) => (string) $n)->filter()->values()->all();

            return $nomes !== [] ? $nomes : ['EMEF Norte', 'EMEF Sul'];
        } catch (\Throwable) {
            return ['EMEF Norte', 'EMEF Sul'];
        }
    }
}
