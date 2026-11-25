<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Http\Controllers;

use iEducar\Packages\BuritiSolicitacaoServicos\Services\AnexoPdf;
use iEducar\Packages\BuritiSolicitacaoServicos\Services\ChamadoSla;
use iEducar\Packages\BuritiSolicitacaoServicos\Services\FluxoChamado;
use iEducar\Packages\BuritiSolicitacaoServicos\Services\PocRegistro;
use iEducar\Packages\BuritiSolicitacaoServicos\Services\SetorChamado;
use iEducar\Packages\BuritiSolicitacaoServicos\Services\TimelineChamado;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\Acesso;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\Processos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SolicitacaoController extends Controller
{
    private const array PRIORIDADES = ['alta', 'media', 'baixa'];

    public function index(): View
    {
        $this->exigir(Processos::PAINEL);
        $chamados = $this->chamados();
        $abertos = array_values(array_filter($chamados, static fn ($c) => FluxoChamado::estaAberto((string) ($c['situacao'] ?? 'aberto'))));
        $prazo = 0;
        $atrasadosLista = [];
        foreach ($abertos as $c) {
            if (ChamadoSla::dentroDoPrazo($c)) {
                $prazo++;
            } else {
                $atrasadosLista[] = $c;
            }
        }
        $atrasados = count($atrasadosLista);
        $resolvidos = count(array_filter($chamados, static fn ($c) => in_array($c['situacao'] ?? '', [FluxoChamado::STATUS_RESOLVIDO, FluxoChamado::STATUS_FECHADO, 'deferido'], true)));
        $setores = count(SetorChamado::listar());
        $porPrioridade = ['alta' => 0, 'media' => 0, 'baixa' => 0];
        foreach ($abertos as $c) {
            $p = mb_strtolower((string) ($c['prioridade'] ?? 'media'));
            if (! isset($porPrioridade[$p])) {
                $p = 'media';
            }
            $porPrioridade[$p]++;
        }

        $analise = [];
        foreach (array_slice($atrasadosLista, 0, 8) as $c) {
            $analise[] = [
                'titulo' => '#'.$c['id'].' · '.$c['assunto'],
                'meta' => ($c['escola'] ?? '—').' · '.($c['setor'] ?? 'sem setor'),
                'corpo' => 'Prioridade '.$c['prioridade'].' · aberto '.$c['aberto_em'],
                'badge' => 'SLA estourado',
                'badge_tom' => 'fail',
                'url' => '/solicitacao-servicos/chamado/'.$c['id'],
            ];
        }
        foreach (['alta', 'media', 'baixa'] as $prio) {
            $analise[] = [
                'titulo' => 'Prioridade '.$prio,
                'meta' => 'Chamados abertos nesta prioridade',
                'valor' => (string) $porPrioridade[$prio],
                'badge' => $prio === 'alta' && $porPrioridade[$prio] > 0 ? 'Atenção' : 'Fila',
                'badge_tom' => $prio === 'alta' && $porPrioridade[$prio] > 0 ? 'fail' : 'ok',
                'url' => '/solicitacao-servicos/abertos',
            ];
        }

        return view('solicitacao::painel', [
            'kpis' => [
                ['rotulo' => 'Abertos', 'valor' => count($abertos), 'icone' => 'fa-ticket', 'tom' => 'var(--cor)'],
                ['rotulo' => 'No prazo', 'valor' => $prazo, 'icone' => 'fa-clock-o', 'tom' => 'var(--ok)'],
                ['rotulo' => 'Atrasados', 'valor' => $atrasados, 'icone' => 'fa-warning', 'tom' => $atrasados ? 'var(--erro)' : 'var(--ok)'],
                ['rotulo' => 'Resolvidos', 'valor' => $resolvidos, 'icone' => 'fa-check', 'tom' => 'var(--destaque)'],
                ['rotulo' => 'Setores', 'valor' => $setores, 'icone' => 'fa-sitemap', 'tom' => '#0e7490'],
                ['rotulo' => 'Prioridade alta', 'valor' => $porPrioridade['alta'], 'icone' => 'fa-fire', 'tom' => $porPrioridade['alta'] ? 'var(--ambar)' : 'var(--ok)'],
            ],
            'destaques' => [
                [
                    'titulo' => 'Central de chamados',
                    'meta' => 'Respostas · setores · PDF · feedback · '.$this->fonte(),
                    'icone' => 'fa-ticket',
                    'tom' => $atrasados ? 'var(--erro)' : 'var(--cor)',
                    'corpo' => $atrasados ? "{$atrasados} chamado(s) fora do SLA." : 'Fila sob controle — nenhum atraso de prazo.',
                ],
            ],
            'analise' => $analise,
            'analiseTitulo' => 'SLA e prioridades',
            'resumo' => 'Chamados SME/escola · abrir, encaminhar, responder e medir SLA.',
            'atalhos' => [
                ['url' => '/solicitacao-servicos/novo', 'icone' => 'fa-plus', 'rotulo' => '1. Abrir', 'meta' => 'Demanda + PDF'],
                ['url' => '/solicitacao-servicos/setores', 'icone' => 'fa-sitemap', 'rotulo' => '2. Setores', 'meta' => 'Responsáveis'],
                ['url' => '/solicitacao-servicos/abertos', 'icone' => 'fa-comments', 'rotulo' => '3. Atender', 'meta' => 'Respostas e andamentos'],
                ['url' => '/solicitacao-servicos/sla', 'icone' => 'fa-clock-o', 'rotulo' => 'SLA', 'meta' => 'Prazos'],
                ['url' => '/solicitacao-servicos/relatorios', 'icone' => 'fa-bar-chart', 'rotulo' => 'Indicadores', 'meta' => 'Feedback incluso'],
            ],
            'fluxoTitulo' => 'Fluxo do chamado',
        ]);
    }

    public function abertos(Request $request): View
    {
        $this->exigir(Processos::ABERTOS);
        $filtroSetor = trim((string) $request->query('setor', ''));
        $filtroResp = trim((string) $request->query('responsavel', ''));
        $filtroStatus = trim((string) $request->query('status', ''));
        $linhas = [];
        foreach ($this->chamados() as $c) {
            $status = (string) ($c['situacao'] ?? 'aberto');
            if ($filtroStatus === '' && ! FluxoChamado::estaAberto($status) && $status !== 'deferido') {
                continue;
            }
            if ($filtroStatus !== '' && $status !== $filtroStatus) {
                continue;
            }
            if ($filtroSetor !== '' && ! str_contains(mb_strtolower((string) ($c['setor'] ?? '')), mb_strtolower($filtroSetor))) {
                continue;
            }
            if ($filtroResp !== '' && ! str_contains(mb_strtolower((string) ($c['responsavel'] ?? '')), mb_strtolower($filtroResp))) {
                continue;
            }
            $ok = ChamadoSla::dentroDoPrazo($c);
            $prio = (string) ($c['prioridade'] ?? 'media');
            $prioRotulo = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'][$prio] ?? $prio;
            $linhas[] = [
                'id' => $c['id'],
                'titulo' => '#'.$c['id'].' · '.$c['assunto'],
                'meta' => $c['escola'].' · '.($c['setor'] ?? 'sem setor').' · '.($c['responsavel'] ?? 'sem responsável'),
                'badge' => FluxoChamado::rotulo($status),
                'badge_tom' => FluxoChamado::tom($status),
                'corpo' => ($ok ? 'No prazo' : 'Prazo estourado').' · urgência '.$prioRotulo.' · aberto '.$c['aberto_em'],
                'url' => url('/solicitacao-servicos/chamado/'.$c['id']),
            ];
        }

        $setoresFiltro = [];
        foreach ($this->chamados() as $c) {
            $nomeSetor = trim((string) ($c['setor'] ?? ''));
            if ($nomeSetor !== '') {
                $setoresFiltro[$nomeSetor] = $nomeSetor;
            }
        }
        foreach (SetorChamado::listar() as $s) {
            $nomeSetor = trim((string) ($s['nome'] ?? ''));
            if ($nomeSetor !== '') {
                $setoresFiltro[$nomeSetor] = $nomeSetor;
            }
        }
        ksort($setoresFiltro, SORT_NATURAL | SORT_FLAG_CASE);

        return view('solicitacao::abertos', [
            'linhas' => $linhas,
            'filtros' => ['setor' => $filtroSetor, 'responsavel' => $filtroResp, 'status' => $filtroStatus],
            'statuses' => FluxoChamado::todos(),
            'setoresFiltro' => array_values($setoresFiltro),
            'podeDeferir' => Acesso::permite(Processos::DEFERIR, 'gravar'),
            'podeIndeferir' => Acesso::permite(Processos::INDEFERIR, 'gravar'),
        ]);
    }

    public function sla(): View
    {
        $this->exigir(Processos::SLA);
        $linhas = [];
        foreach ($this->chamados() as $c) {
            $status = (string) ($c['situacao'] ?? 'aberto');
            $ok = ChamadoSla::dentroDoPrazo($c);
            $linhas[] = [
                'titulo' => '#'.$c['id'].' · '.$c['prioridade'],
                'meta' => $c['assunto'].' · '.FluxoChamado::rotulo($status),
                'badge' => $ok ? 'SLA OK' : 'SLA estourado',
                'badge_tom' => $ok ? 'ok' : 'fail',
                'corpo' => ($c['setor'] ?? '—').' · '.($c['responsavel'] ?? '—'),
            ];
        }

        return view('solicitacao::sla', [
            'linhas' => $linhas,
            'slaHoras' => ChamadoSla::horasPadrao(),
        ]);
    }

    public function novo(): View
    {
        $this->exigir(Processos::NOVO);

        return view('solicitacao::novo', [
            'escolas' => $this->escolasDaRede(),
            'prioridades' => self::PRIORIDADES,
            'categorias' => $this->categoriasLista(),
            'setores' => SetorChamado::listar(),
            'ativo' => (bool) config('solicitacao.novo_chamado_ativo', true),
            'slaHoras' => ChamadoSla::horasPadrao(),
            'anexoMaxKb' => AnexoPdf::maxKb(),
        ]);
    }

    public function salvarChamado(Request $request): RedirectResponse
    {
        $this->exigir(Processos::NOVO, 'gravar');
        if (! (bool) config('solicitacao.novo_chamado_ativo', true)) {
            return back()->with('erro', 'Abertura de chamado desabilitada por config.');
        }
        if (! PocRegistro::disponivel()) {
            return back()->with('erro', 'Schema solicitacao_servicos.poc_registro ausente — rode migrate.');
        }

        $escola = trim((string) $request->input('escola', ''));
        $assunto = trim((string) $request->input('assunto', ''));
        $prioridade = (string) $request->input('prioridade', 'media');
        $categoria = trim((string) $request->input('categoria', 'geral')) ?: 'geral';
        $descricao = trim((string) $request->input('descricao', ''));
        $setorId = (int) $request->input('setor_id', 0);
        $setorNome = trim((string) $request->input('setor', ''));

        if ($escola === '' || $assunto === '') {
            return back()->withInput()->with('erro', 'Informe a escola e o assunto do pedido.');
        }

        $escolasValidas = array_map(
            static fn (array $e): string => (string) $e['nome'],
            $this->escolasDaRede()
        );
        if ($escolasValidas !== [] && ! in_array($escola, $escolasValidas, true)) {
            return back()->withInput()->with('erro', 'Escola não encontrada na rede. Escolha uma escola da lista.');
        }

        if (! in_array($prioridade, self::PRIORIDADES, true)) {
            $prioridade = 'media';
        }

        $cats = array_map(
            static fn (array $c): string => (string) $c['nome'],
            $this->categoriasLista()
        );
        if ($cats !== [] && ! in_array($categoria, $cats, true)) {
            return back()->withInput()->with('erro', 'Tipo de pedido inválido. Escolha um tipo da lista ou cadastre em «Tipos de pedido».');
        }

        $setor = $setorId > 0 ? SetorChamado::buscar($setorId) : null;
        if ($setorId > 0 && $setor === null) {
            return back()->withInput()->with('erro', 'Setor não encontrado. Escolha outro ou deixe em triagem da SME.');
        }
        if ($setor !== null) {
            if (array_key_exists('ativo', $setor) && ! $setor['ativo']) {
                return back()->withInput()->with('erro', 'Este setor está inativo. Escolha outro setor.');
            }
            $setorNome = $setor['nome'];
        }

        $user = Auth::user();
        $autor = $user?->name ?? $user?->login ?? $user?->email ?? 'usuário';
        $abertoEm = now()->toIso8601String();
        $id = PocRegistro::gravar('chamado', $assunto, $escola.' · '.$prioridade, [
            'escola' => $escola,
            'assunto' => $assunto,
            'prioridade' => $prioridade,
            'categoria' => $categoria,
            'descricao' => $descricao,
            'situacao' => FluxoChamado::STATUS_ABERTO,
            'aberto_em' => $abertoEm,
            'setor_id' => $setorId > 0 ? $setorId : null,
            'setor' => $setorNome !== '' ? $setorNome : null,
            'responsavel_id' => null,
            'responsavel' => null,
            'solicitante_id' => $user?->getKey(),
            'solicitante' => $autor,
            'anexo_ids' => [],
            'feedback' => null,
        ]);

        TimelineChamado::andamento($id, 'Chamado aberto', $autor, null, FluxoChamado::STATUS_ABERTO, $setorNome !== '' ? $setorNome : null);

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraProcesso(
                Processos::ABERTOS,
                "Novo chamado #{$id}: {$assunto} ({$escola} · {$prioridade}).",
                '/solicitacao-servicos/chamado/'.$id,
            );
        }

        try {
            if ($request->hasFile('anexo')) {
                $anexo = AnexoPdf::salvar($request->file('anexo'), $id, null, $autor);
                if ($anexo !== null) {
                    $reg = PocRegistro::buscar($id);
                    if ($reg !== null) {
                        $p = $reg['payload'];
                        $p['anexo_ids'] = [$anexo['id']];
                        PocRegistro::atualizarPayload($id, $p);
                    }
                }
            }
        } catch (Throwable $e) {
            return redirect('/solicitacao-servicos/chamado/'.$id)
                ->with('erro', 'Chamado aberto, mas o anexo falhou: '.$e->getMessage());
        }

        $this->auditar($id, 'abertura', "Chamado #{$id} aberto ({$categoria}/{$prioridade}".($setorNome !== '' ? " · {$setorNome}" : '').').');

        return redirect('/solicitacao-servicos/chamado/'.$id)
            ->with('ok', "Pedido #{$id} «{$assunto}» aberto para {$escola}.");
    }

    public function categorias(): View
    {
        $this->exigir(Processos::CATEGORIAS);

        return view('solicitacao::categorias', [
            'categorias' => $this->categoriasLista(),
            'podeGravar' => Acesso::permite(Processos::CATEGORIAS, 'gravar'),
        ]);
    }

    public function salvarCategoria(Request $request): RedirectResponse
    {
        $this->exigir(Processos::CATEGORIAS, 'gravar');
        if (! PocRegistro::disponivel()) {
            return back()->with('erro', 'Schema ausente.');
        }
        $nome = trim((string) $request->input('nome', ''));
        $sla = (int) $request->input('sla_horas', 48);
        if ($nome === '') {
            return back()->with('erro', 'Informe o nome da categoria.');
        }
        $id = PocRegistro::gravar('categoria', $nome, $sla.'h', [
            'nome' => $nome,
            'sla_horas' => max(1, $sla),
            'ativo' => true,
        ]);
        $this->auditar($id, 'cadastro_categoria', "Categoria '{$nome}' cadastrada (SLA {$sla}h).");

        return back()->with('ok', "Categoria '{$nome}' cadastrada.");
    }

    public function setores(): View
    {
        $this->exigir(Processos::SETORES);

        return view('solicitacao::setores', [
            'setores' => SetorChamado::listar(false),
            'usuarios' => $this->usuariosRede(),
            'podeGravar' => Acesso::permite(Processos::SETORES, 'gravar'),
        ]);
    }

    public function salvarSetor(Request $request): RedirectResponse
    {
        $this->exigir(Processos::SETORES, 'gravar');
        if (! PocRegistro::disponivel()) {
            return back()->with('erro', 'Schema ausente — rode migrate.');
        }
        $nome = trim((string) $request->input('nome', ''));
        $descricao = trim((string) $request->input('descricao', ''));
        if ($nome === '') {
            return back()->with('erro', 'Nome do setor é obrigatório.');
        }
        $responsaveis = $this->responsaveisDoRequest($request);
        $id = SetorChamado::gravar($nome, $descricao, $responsaveis, true);
        $this->auditar($id, 'cadastro_setor', "Setor '{$nome}' cadastrado.");

        return back()->with('ok', "Setor '{$nome}' cadastrado.");
    }

    public function atualizarSetor(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::SETORES, 'gravar');
        $nome = trim((string) $request->input('nome', ''));
        $descricao = trim((string) $request->input('descricao', ''));
        $ativo = $request->boolean('ativo', true);
        if ($nome === '') {
            return back()->with('erro', 'Nome do setor é obrigatório.');
        }
        $ok = SetorChamado::atualizar($id, $nome, $descricao, $this->responsaveisDoRequest($request), $ativo);
        if (! $ok) {
            return back()->with('erro', 'Setor não encontrado.');
        }
        $this->auditar($id, 'atualiza_setor', "Setor '{$nome}' atualizado.");

        return back()->with('ok', "Setor '{$nome}' atualizado.");
    }

    public function relatorios(): View
    {
        $this->exigir(Processos::RELATORIOS);
        $chamados = $this->chamados();
        $porSituacao = [];
        $porPrioridade = [];
        $porCategoria = [];
        $porSetor = [];
        $notas = [];
        foreach ($chamados as $c) {
            $s = (string) ($c['situacao'] ?? 'aberto');
            $p = (string) ($c['prioridade'] ?? 'media');
            $k = (string) ($c['categoria'] ?? 'geral');
            $setor = (string) ($c['setor'] ?? 'sem setor');
            $porSituacao[$s] = ($porSituacao[$s] ?? 0) + 1;
            $porPrioridade[$p] = ($porPrioridade[$p] ?? 0) + 1;
            $porCategoria[$k] = ($porCategoria[$k] ?? 0) + 1;
            $porSetor[$setor] = ($porSetor[$setor] ?? 0) + 1;
            if (! empty($c['feedback']['nota'])) {
                $notas[] = (int) $c['feedback']['nota'];
            }
        }

        return view('solicitacao::relatorios', [
            'total' => count($chamados),
            'porSituacao' => $porSituacao,
            'porPrioridade' => $porPrioridade,
            'porCategoria' => $porCategoria,
            'porSetor' => $porSetor,
            'mediaFeedback' => $notas !== [] ? round(array_sum($notas) / count($notas), 1) : null,
            'fonte' => $this->fonte(),
        ]);
    }

    public function auditoria(Request $request): View
    {
        $this->exigir(Processos::AUDITORIA);
        $filtroUsuario = trim((string) $request->query('usuario', ''));
        $eventos = [];
        foreach (PocRegistro::listar('auditoria', 100) as $r) {
            $p = $r['payload'];
            $usuario = (string) ($p['usuario'] ?? 'sistema');
            if ($filtroUsuario !== '' && ! str_contains(mb_strtolower($usuario), mb_strtolower($filtroUsuario))) {
                continue;
            }
            $eventos[] = [
                'id' => $r['id'],
                'quando' => $r['created_at'] ?? '—',
                'acao' => (string) ($p['acao'] ?? $r['titulo']),
                'detalhe' => (string) ($p['detalhe'] ?? $r['meta'] ?? ''),
                'usuario' => $usuario,
                'chamado_id' => $p['chamado_id'] ?? null,
            ];
        }

        return view('solicitacao::auditoria', [
            'eventos' => $eventos,
            'filtroUsuario' => $filtroUsuario,
        ]);
    }

    public function detalhe(int $id): View|RedirectResponse
    {
        $this->exigir(Processos::ABERTOS);
        $chamado = $this->chamadoPorId($id);
        if ($chamado === null) {
            return redirect('/solicitacao-servicos/abertos')->with('erro', 'Chamado não encontrado.');
        }
        $status = (string) ($chamado['situacao'] ?? 'aberto');
        $anexos = AnexoPdf::doChamado($id);
        $anexosPorId = [];
        foreach ($anexos as $a) {
            $anexosPorId[$a['id']] = $a;
        }

        return view('solicitacao::detalhe', [
            'chamado' => $chamado,
            'timeline' => TimelineChamado::doChamado($id),
            'anexos' => $anexos,
            'anexosPorId' => $anexosPorId,
            'setores' => SetorChamado::listar(),
            'proximos' => FluxoChamado::proximos($status),
            'anexoMaxKb' => AnexoPdf::maxKb(),
            'podeResponder' => Acesso::permite(Processos::RESPONDER, 'gravar') && FluxoChamado::podeResponder($status),
            'podeEncaminhar' => Acesso::permite(Processos::ENCAMINHAR, 'gravar') && FluxoChamado::podeEncaminhar($status),
            'podeDeferir' => Acesso::permite(Processos::DEFERIR, 'gravar') && FluxoChamado::estaAberto($status),
            'podeIndeferir' => Acesso::permite(Processos::INDEFERIR, 'gravar') && FluxoChamado::estaAberto($status),
            'podeFeedback' => FluxoChamado::podeFeedback($status) && empty($chamado['feedback']),
            'rotulosStatus' => array_combine(FluxoChamado::todos(), array_map([FluxoChamado::class, 'rotulo'], FluxoChamado::todos())),
        ]);
    }

    public function responder(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::RESPONDER, 'gravar');
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'chamado') {
            return back()->with('erro', 'Chamado não encontrado.');
        }
        $p = $reg['payload'];
        $status = (string) ($p['situacao'] ?? 'aberto');
        if (! FluxoChamado::podeResponder($status)) {
            return back()->with('erro', 'Chamado não aceita novas respostas.');
        }
        $texto = trim((string) $request->input('texto', ''));
        if ($texto === '') {
            return back()->with('erro', 'Informe o texto da resposta.');
        }
        $autor = $this->nomeUsuario();
        $msgId = TimelineChamado::responder($id, $texto, $autor, Auth::id());
        $anexoIds = [];
        try {
            if ($request->hasFile('anexo')) {
                $anexo = AnexoPdf::salvar($request->file('anexo'), $id, $msgId, $autor);
                if ($anexo !== null) {
                    $anexoIds[] = $anexo['id'];
                    PocRegistro::atualizarPayload($msgId, array_merge(
                        PocRegistro::buscar($msgId)['payload'] ?? [],
                        ['anexo_ids' => $anexoIds]
                    ));
                }
            }
        } catch (Throwable $e) {
            return redirect('/solicitacao-servicos/chamado/'.$id)->with('erro', 'Resposta gravada, anexo falhou: '.$e->getMessage());
        }

        if ($status === FluxoChamado::STATUS_ABERTO) {
            $this->transitar($id, $p, FluxoChamado::STATUS_ATENDIMENTO, 'Primeira resposta — em atendimento');
        } elseif ($status === FluxoChamado::STATUS_AGUARDANDO) {
            $this->transitar($id, $p, FluxoChamado::STATUS_ATENDIMENTO, 'Retomado após resposta');
        }

        $this->auditar($id, 'resposta', mb_substr($texto, 0, 120));

        return redirect('/solicitacao-servicos/chamado/'.$id)->with('ok', 'Resposta registrada.');
    }

    public function registrarAndamento(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::RESPONDER, 'gravar');
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'chamado') {
            return back()->with('erro', 'Chamado não encontrado.');
        }
        $texto = trim((string) $request->input('texto', ''));
        if ($texto === '') {
            return back()->with('erro', 'Descreva o andamento.');
        }
        $p = $reg['payload'];
        TimelineChamado::andamento(
            $id,
            $texto,
            $this->nomeUsuario(),
            (string) ($p['situacao'] ?? ''),
            null,
            $p['setor'] ?? null,
            $p['responsavel'] ?? null,
        );
        $this->auditar($id, 'andamento', mb_substr($texto, 0, 120));

        return redirect('/solicitacao-servicos/chamado/'.$id)->with('ok', 'Andamento registrado.');
    }

    public function encaminhar(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::ENCAMINHAR, 'gravar');
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'chamado') {
            return back()->with('erro', 'Chamado não encontrado.');
        }
        $p = $reg['payload'];
        $status = (string) ($p['situacao'] ?? 'aberto');
        if (! FluxoChamado::podeEncaminhar($status)) {
            return back()->with('erro', 'Chamado não pode ser encaminhado neste status.');
        }

        $setorId = (int) $request->input('setor_id', 0);
        $setor = $setorId > 0 ? SetorChamado::buscar($setorId) : null;
        $setorNome = $setor['nome'] ?? trim((string) $request->input('setor', ''));
        $respId = (int) $request->input('responsavel_id', 0);
        $respNome = trim((string) $request->input('responsavel', ''));
        if ($setor !== null && $respId > 0) {
            foreach ($setor['responsaveis'] as $r) {
                if ((int) $r['user_id'] === $respId) {
                    $respNome = $r['nome'];
                    break;
                }
            }
        }
        if ($setorNome === '') {
            return back()->with('erro', 'Selecione o setor de destino.');
        }

        $de = $status;
        $p['setor_id'] = $setorId > 0 ? $setorId : ($p['setor_id'] ?? null);
        $p['setor'] = $setorNome;
        $p['responsavel_id'] = $respId > 0 ? $respId : null;
        $p['responsavel'] = $respNome !== '' ? $respNome : null;
        if ($status === FluxoChamado::STATUS_ABERTO) {
            $p['situacao'] = FluxoChamado::STATUS_TRIAGEM;
        }
        PocRegistro::atualizarPayload($id, $p, meta: ($p['escola'] ?? '').' · '.($p['situacao'] ?? ''));

        $txt = 'Encaminhado para '.$setorNome.($respNome !== '' ? ' / '.$respNome : '');
        TimelineChamado::andamento($id, $txt, $this->nomeUsuario(), $de, $p['situacao'] ?? $de, $setorNome, $respNome !== '' ? $respNome : null);
        $this->auditar($id, 'encaminhamento', $txt);

        return redirect('/solicitacao-servicos/chamado/'.$id)->with('ok', $txt.'.');
    }

    public function assumir(int $id): RedirectResponse
    {
        $this->exigir(Processos::ENCAMINHAR, 'gravar');
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'chamado') {
            return back()->with('erro', 'Chamado não encontrado.');
        }
        $p = $reg['payload'];
        $de = (string) ($p['situacao'] ?? 'aberto');
        $nome = $this->nomeUsuario();
        $p['responsavel_id'] = Auth::id();
        $p['responsavel'] = $nome;
        if (in_array($de, [FluxoChamado::STATUS_ABERTO, FluxoChamado::STATUS_TRIAGEM], true)) {
            $p['situacao'] = FluxoChamado::STATUS_ATENDIMENTO;
        }
        PocRegistro::atualizarPayload($id, $p, meta: ($p['escola'] ?? '').' · '.($p['situacao'] ?? ''));
        TimelineChamado::andamento($id, $nome.' assumiu o chamado', $nome, $de, $p['situacao'] ?? $de, $p['setor'] ?? null, $nome);
        $this->auditar($id, 'assumir', $nome.' assumiu');

        return redirect('/solicitacao-servicos/chamado/'.$id)->with('ok', 'Você é o responsável pelo chamado.');
    }

    public function alterarStatus(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::DEFERIR, 'gravar');
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'chamado') {
            return back()->with('erro', 'Chamado não encontrado.');
        }
        $p = $reg['payload'];
        $de = (string) ($p['situacao'] ?? 'aberto');
        $para = trim((string) $request->input('status', ''));
        $nota = trim((string) $request->input('nota', ''));
        if (! FluxoChamado::podeTransitar($de, $para)) {
            return back()->with('erro', 'Transição de status não permitida.');
        }
        $this->transitar($id, $p, $para, $nota !== '' ? $nota : ('Status → '.FluxoChamado::rotulo($para)));

        return redirect('/solicitacao-servicos/chamado/'.$id)->with('ok', 'Status atualizado para '.FluxoChamado::rotulo($para).'.');
    }

    public function feedback(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::ABERTOS);
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'chamado') {
            return back()->with('erro', 'Chamado não encontrado.');
        }
        $p = $reg['payload'];
        $status = (string) ($p['situacao'] ?? '');
        if (! FluxoChamado::podeFeedback($status)) {
            return back()->with('erro', 'Feedback só após resolução.');
        }
        if (! empty($p['feedback'])) {
            return back()->with('erro', 'Feedback já registrado.');
        }
        $nota = (int) $request->input('nota', 0);
        $comentario = trim((string) $request->input('comentario', ''));
        if ($nota < 1 || $nota > 5) {
            return back()->with('erro', 'Nota de 1 a 5.');
        }
        $p['feedback'] = [
            'nota' => $nota,
            'comentario' => $comentario,
            'autor' => $this->nomeUsuario(),
            'em' => now()->toIso8601String(),
        ];
        if ($status === FluxoChamado::STATUS_RESOLVIDO) {
            $p['situacao'] = FluxoChamado::STATUS_FECHADO;
            $p['fechado_em'] = now()->toIso8601String();
        }
        PocRegistro::atualizarPayload($id, $p, meta: ($p['escola'] ?? '').' · '.($p['situacao'] ?? 'fechado'));
        TimelineChamado::andamento($id, "Feedback: {$nota}/5".($comentario !== '' ? ' — '.$comentario : ''), $this->nomeUsuario(), $status, $p['situacao'] ?? $status);
        $this->auditar($id, 'feedback', "Nota {$nota}/5");

        return redirect('/solicitacao-servicos/chamado/'.$id)->with('ok', 'Obrigado pelo feedback.');
    }

    public function deferir(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::DEFERIR, 'gravar');
        $parecer = trim((string) $request->input('parecer', 'Resolvido pela equipe.'));

        return $this->decidir($id, FluxoChamado::STATUS_RESOLVIDO, $parecer);
    }

    public function indeferir(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::INDEFERIR, 'gravar');

        return $this->decidir($id, FluxoChamado::STATUS_INDEFERIDO, trim((string) $request->input('parecer', 'Indeferido.')));
    }

    public function baixarAnexo(int $id): BinaryFileResponse|RedirectResponse|Response
    {
        $this->exigir(Processos::ABERTOS);
        $meta = AnexoPdf::meta($id);
        $path = AnexoPdf::caminhoAbsoluto($id);
        if ($meta === null || $path === null) {
            return redirect('/solicitacao-servicos/abertos')->with('erro', 'Anexo não encontrado.');
        }

        return response()->download($path, $meta['nome'], ['Content-Type' => 'application/pdf']);
    }

    private function decidir(int $id, string $situacao, string $parecer): RedirectResponse
    {
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'chamado') {
            return back()->with('erro', 'Chamado não encontrado (apenas registros persistidos).');
        }
        $payload = $reg['payload'];
        $de = (string) ($payload['situacao'] ?? 'aberto');
        // Compat: deferido legado → resolvido
        if ($situacao === 'deferido') {
            $situacao = FluxoChamado::STATUS_RESOLVIDO;
        }
        if (! FluxoChamado::estaAberto($de) && $de !== 'aberto') {
            return back()->with('erro', 'Chamado já encerrado.');
        }
        $payload['situacao'] = $situacao;
        $payload['parecer'] = $parecer !== '' ? $parecer : ucfirst($situacao);
        $payload['decidido_em'] = now()->toIso8601String();
        $payload['decidido_por'] = $this->nomeUsuario();
        if ($situacao === FluxoChamado::STATUS_RESOLVIDO) {
            $payload['resolvido_em'] = $payload['decidido_em'];
        }
        PocRegistro::atualizarPayload($id, $payload, meta: ($payload['escola'] ?? '').' · '.$situacao);
        TimelineChamado::andamento($id, $payload['parecer'], $this->nomeUsuario(), $de, $situacao, $payload['setor'] ?? null, $payload['responsavel'] ?? null);
        $this->auditar($id, $situacao, $payload['parecer']);

        return redirect('/solicitacao-servicos/chamado/'.$id)
            ->with('ok', 'Chamado #'.$id.' '.FluxoChamado::rotulo($situacao).'.');
    }

    /** @param array<string,mixed> $payload */
    private function transitar(int $id, array $payload, string $para, string $texto): void
    {
        $de = (string) ($payload['situacao'] ?? 'aberto');
        $payload['situacao'] = $para;
        if ($para === FluxoChamado::STATUS_RESOLVIDO) {
            $payload['resolvido_em'] = now()->toIso8601String();
        }
        if ($para === FluxoChamado::STATUS_FECHADO) {
            $payload['fechado_em'] = now()->toIso8601String();
        }
        PocRegistro::atualizarPayload($id, $payload, meta: ($payload['escola'] ?? '').' · '.$para);
        TimelineChamado::andamento($id, $texto, $this->nomeUsuario(), $de, $para, $payload['setor'] ?? null, $payload['responsavel'] ?? null);
        $this->auditar($id, 'status_'.$para, $texto);
    }

    private function exigir(int $processo, string $acao = 'ver'): void
    {
        if (! Acesso::permite($processo, $acao)) {
            // RESPONDER/ENCAMINHAR/SETORES: fallback para permissões de operação se menu novo ainda não migrado
            $fallback = match ($processo) {
                Processos::RESPONDER => Processos::DEFERIR,
                Processos::ENCAMINHAR => Processos::DEFERIR,
                Processos::SETORES => Processos::CATEGORIAS,
                default => null,
            };
            if ($fallback === null || ! Acesso::permite($fallback, $acao)) {
                abort(403, 'Sem permissão para este processo ('.$processo.').');
            }
        }
    }

    private function auditar(?int $chamadoId, string $acao, string $detalhe): void
    {
        if (! PocRegistro::disponivel()) {
            return;
        }
        PocRegistro::gravar('auditoria', $acao, $detalhe, [
            'acao' => $acao,
            'detalhe' => $detalhe,
            'chamado_id' => $chamadoId,
            'usuario' => $this->nomeUsuario(),
            'em' => now()->toIso8601String(),
        ]);
    }

    private function nomeUsuario(): string
    {
        $u = Auth::user();

        return $u?->name ?? $u?->login ?? $u?->email ?? 'sistema';
    }

    /**
     * @return list<array{user_id:int,nome:string}>
     */
    private function responsaveisDoRequest(Request $request): array
    {
        $ids = array_map('intval', (array) $request->input('responsavel_ids', []));
        $lista = [];
        foreach ($this->usuariosRede() as $u) {
            if (in_array((int) $u['id'], $ids, true)) {
                $lista[] = ['user_id' => (int) $u['id'], 'nome' => (string) $u['nome']];
            }
        }
        $extras = trim((string) $request->input('responsaveis_texto', ''));
        if ($extras !== '') {
            foreach (preg_split('/[\n,;]+/', $extras) ?: [] as $nome) {
                $nome = trim($nome);
                if ($nome !== '') {
                    $lista[] = ['user_id' => 0, 'nome' => $nome];
                }
            }
        }

        return SetorChamado::normalizarResponsaveis($lista);
    }

    /** @return list<array{nome:string,sla_horas:int}> */
    private function categoriasLista(): array
    {
        $out = [];
        foreach (PocRegistro::listar('categoria') as $r) {
            $p = $r['payload'];
            $out[] = [
                'nome' => (string) ($p['nome'] ?? $r['titulo']),
                'sla_horas' => (int) ($p['sla_horas'] ?? 48),
            ];
        }
        if ($out === []) {
            return [
                ['nome' => 'Manutenção', 'sla_horas' => 24],
                ['nome' => 'TI / Informática', 'sla_horas' => 48],
                ['nome' => 'Geral', 'sla_horas' => 72],
            ];
        }

        return $out;
    }

    /** @return array<string,mixed>|null */
    private function chamadoPorId(int $id): ?array
    {
        foreach ($this->chamados() as $c) {
            if ((int) $c['id'] === $id) {
                return $c;
            }
        }

        return null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function chamados(): array
    {
        if (PocRegistro::contar('chamado') > 0) {
            $out = [];
            foreach (PocRegistro::listar('chamado') as $r) {
                $p = $r['payload'];
                $situacao = (string) ($p['situacao'] ?? (isset($p['resolvido_em']) ? FluxoChamado::STATUS_RESOLVIDO : FluxoChamado::STATUS_ABERTO));
                if ($situacao === 'deferido') {
                    $situacao = FluxoChamado::STATUS_RESOLVIDO;
                }
                $out[] = [
                    'id' => $r['id'],
                    'escola' => (string) ($p['escola'] ?? '—'),
                    'assunto' => (string) ($p['assunto'] ?? $r['titulo']),
                    'prioridade' => (string) ($p['prioridade'] ?? 'media'),
                    'categoria' => (string) ($p['categoria'] ?? 'geral'),
                    'situacao' => $situacao,
                    'aberto_em' => (string) ($p['aberto_em'] ?? ($r['created_at'] ?? date('c'))),
                    'descricao' => (string) ($p['descricao'] ?? ''),
                    'parecer' => (string) ($p['parecer'] ?? ''),
                    'resolvido_em' => isset($p['resolvido_em']) ? (string) $p['resolvido_em'] : null,
                    'setor_id' => isset($p['setor_id']) ? (int) $p['setor_id'] : null,
                    'setor' => isset($p['setor']) ? (string) $p['setor'] : null,
                    'responsavel_id' => isset($p['responsavel_id']) ? (int) $p['responsavel_id'] : null,
                    'responsavel' => isset($p['responsavel']) ? (string) $p['responsavel'] : null,
                    'solicitante' => isset($p['solicitante']) ? (string) $p['solicitante'] : null,
                    'anexo_ids' => array_map('intval', (array) ($p['anexo_ids'] ?? [])),
                    'feedback' => is_array($p['feedback'] ?? null) ? $p['feedback'] : null,
                ];
            }

            return $out;
        }

        if (! (bool) config('solicitacao.seed_demo_se_vazio', true)) {
            return [];
        }

        return $this->chamadosDemo();
    }

    private function fonte(): string
    {
        return PocRegistro::contar('chamado') > 0
            ? 'solicitacao_servicos.poc_registro'
            : 'demo';
    }

    /** @return list<array<string,mixed>> */
    private function chamadosDemo(): array
    {
        return [
            ['id' => 501, 'escola' => 'EMEF Norte', 'assunto' => 'Manutenção elétrica', 'prioridade' => 'alta', 'categoria' => 'manutencao', 'situacao' => 'aberto', 'aberto_em' => '2026-11-24T07:00:00-03:00', 'setor' => 'Manutenção predial', 'responsavel' => null, 'feedback' => null],
            ['id' => 502, 'escola' => 'EMEF Sul', 'assunto' => 'Troca de lâmpadas', 'prioridade' => 'media', 'categoria' => 'manutencao', 'situacao' => 'em_atendimento', 'aberto_em' => '2026-11-22T10:00:00-03:00', 'setor' => 'Manutenção predial', 'responsavel' => 'Equipe SME', 'feedback' => null],
            ['id' => 503, 'escola' => 'EMEF Norte', 'assunto' => 'Pintura de muro', 'prioridade' => 'baixa', 'categoria' => 'geral', 'situacao' => 'triagem', 'aberto_em' => '2026-11-10T09:00:00-03:00', 'setor' => 'Administrativo', 'responsavel' => null, 'feedback' => null],
            ['id' => 504, 'escola' => 'EMEF Sul', 'assunto' => 'Vazamento sanitário', 'prioridade' => 'alta', 'categoria' => 'manutencao', 'situacao' => 'resolvido', 'aberto_em' => '2026-11-23T16:00:00-03:00', 'resolvido_em' => '2026-11-24T08:00:00-03:00', 'setor' => 'Manutenção predial', 'responsavel' => 'Equipe SME', 'feedback' => ['nota' => 5, 'comentario' => 'Rápido']],
        ];
    }

    /** @return list<array{cod:int|null,nome:string}> */
    private function escolasDaRede(): array
    {
        $out = [];
        if (! (bool) config('solicitacao.escolas_do_nucleo', true)) {
            return [['cod' => null, 'nome' => 'EMEF Norte'], ['cod' => null, 'nome' => 'EMEF Sul']];
        }
        if (! Schema::hasTable('pmieducar.escola') || ! Schema::hasTable('cadastro.juridica')) {
            return [['cod' => null, 'nome' => 'EMEF Norte'], ['cod' => null, 'nome' => 'EMEF Sul']];
        }
        try {
            $rows = DB::table('pmieducar.escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->where('pmieducar.escola.ativo', 1)
                ->orderBy('cadastro.juridica.fantasia')
                ->limit(200)
                ->get(['pmieducar.escola.cod_escola as cod', 'cadastro.juridica.fantasia as nome']);
        } catch (Throwable) {
            return [['cod' => null, 'nome' => 'EMEF Norte'], ['cod' => null, 'nome' => 'EMEF Sul']];
        }

        foreach ($rows as $r) {
            $out[] = ['cod' => (int) $r->cod, 'nome' => (string) ($r->nome ?? 'Escola '.$r->cod)];
        }

        return $out !== [] ? $out : [['cod' => null, 'nome' => 'EMEF Norte']];
    }

    /**
     * Responsáveis de setor: quem pode ver/responder/encaminhar no módulo.
     *
     * @return list<array{id:int,nome:string,meta?:string,login?:string}>
     */
    private function usuariosRede(): array
    {
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\UsuariosSelecionaveis::class)) {
            return \iEducar\Packages\BuritiLagunaAmbiente\Support\UsuariosSelecionaveis::paraSelect(
                Auth::user(),
                [
                    \iEducar\Packages\BuritiSolicitacaoServicos\Support\Processos::SETORES,
                    \iEducar\Packages\BuritiSolicitacaoServicos\Support\Processos::RESPONDER,
                    \iEducar\Packages\BuritiSolicitacaoServicos\Support\Processos::ENCAMINHAR,
                ],
            );
        }

        try {
            if (! Schema::hasTable('public.users') && ! Schema::hasTable('users')) {
                return [];
            }
            $q = DB::table('users')->orderBy('name')->limit(150);
            if (Schema::hasColumn('users', 'active')) {
                $q->where('active', true);
            }

            return $q->get(['id', 'name', 'email'])
                ->map(static fn ($u): array => [
                    'id' => (int) $u->id,
                    'nome' => (string) ($u->name ?: $u->email ?: 'Usuário #'.$u->id),
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
