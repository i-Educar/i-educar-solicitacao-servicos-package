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
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
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
                ['url' => '/solicitacao-servicos/novo', 'icone' => 'fa-plus', 'rotulo' => 'Abrir', 'meta' => 'Demanda + PDF'],
                ['url' => '/solicitacao-servicos/setores', 'icone' => 'fa-sitemap', 'rotulo' => 'Setores', 'meta' => 'Responsáveis'],
                ['url' => '/solicitacao-servicos/abertos', 'icone' => 'fa-comments', 'rotulo' => 'Atender', 'meta' => 'Respostas e andamentos'],
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
                'corpo' => ($c['setor'] ?? '—')
                    .' · '.($c['responsavel'] ?? '—')
                    .' · prazo '.ChamadoSla::horasDoChamado($c).'h',
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

        // 5.6.6: Verificar permissão para abrir nesta categoria
        if (!$this->podeAbrirCategoria($categoria)) {
            return back()->withInput()->with('erro', 'Você não tem permissão para abrir chamados na categoria «'.$categoria.'».');
        }

        // 5.6.8: Verificar limite de chamados abertos
        $user = Auth::user();
        $userId = $user?->getKey();
        if ($userId !== null) {
            $erroLimite = $this->verificarLimiteChamados($userId, $escola);
            if ($erroLimite !== null) {
                return back()->withInput()->with('erro', $erroLimite);
            }
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
        $slaHoras = $setor !== null
            ? SetorChamado::normalizarSla($setor['sla_horas'] ?? SetorChamado::SLA_PADRAO)
            : null;
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
            'sla_horas' => $slaHoras,
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
            ->with('ok', "Chamado #{$id} «{$assunto}» aberto para {$escola}"
                .($setorNome !== '' ? " · setor {$setorNome}" : ' · em triagem da SME')
                .'.');
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
        $slaHoras = SetorChamado::normalizarSla($request->input('sla_horas', SetorChamado::SLA_PADRAO));
        $id = SetorChamado::gravar($nome, $descricao, $responsaveis, true, $slaHoras);
        $this->auditar($id, 'cadastro_setor', "Setor '{$nome}' cadastrado (SLA {$slaHoras}h).");

        return redirect('/solicitacao-servicos/setores')->with('ok', "Setor '{$nome}' cadastrado.");
    }

    public function editarSetor(int $id): View
    {
        $this->exigir(Processos::SETORES);
        $setor = SetorChamado::buscar($id);
        if ($setor === null) {
            abort(404, 'Setor não encontrado.');
        }

        return view('solicitacao::setor-editar', [
            'setor' => $setor,
            'usuarios' => $this->usuariosParaSetor($setor['responsaveis'] ?? []),
            'podeGravar' => Acesso::permite(Processos::SETORES, 'gravar'),
        ]);
    }

    public function atualizarSetor(Request $request, int $id): RedirectResponse
    {
        $this->exigir(Processos::SETORES, 'gravar');
        $setor = SetorChamado::buscar($id);
        if ($setor === null) {
            return redirect('/solicitacao-servicos/setores')->with('erro', 'Setor não encontrado.');
        }
        $nome = trim((string) $request->input('nome', ''));
        $descricao = trim((string) $request->input('descricao', ''));
        $ativo = $request->boolean('ativo', true);
        $slaHoras = SetorChamado::normalizarSla($request->input('sla_horas', $setor['sla_horas'] ?? SetorChamado::SLA_PADRAO));
        if ($nome === '') {
            return back()->with('erro', 'Nome do setor é obrigatório.');
        }
        $ok = SetorChamado::atualizar(
            $id,
            $nome,
            $descricao,
            $this->responsaveisDoRequest($request, $setor['responsaveis'] ?? []),
            $ativo,
            $slaHoras
        );
        if (! $ok) {
            return back()->with('erro', 'Não foi possível atualizar o setor.');
        }
        $this->auditar($id, 'atualiza_setor', "Setor '{$nome}' atualizado (SLA {$slaHoras}h).");

        return redirect('/solicitacao-servicos/setores')->with('ok', "Setor '{$nome}' atualizado.");
    }

    public function relatorios(Request $request): View
    {
        $this->exigir(Processos::RELATORIOS);
        
        // 5.6.5: Filtros de período
        $dataInicio = trim((string) $request->query('data_inicio', ''));
        $dataFim = trim((string) $request->query('data_fim', ''));
        $formato = trim((string) $request->query('formato', 'html')); // html, csv, print
        
        $chamados = $this->chamadosPorPeriodo($dataInicio, $dataFim);
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

        // 5.6.5: Exportação via módulo unificado (legado ?formato=csv|pdf|xlsx)
        if (in_array($formato, ['csv', 'pdf', 'xlsx', 'txt'], true)) {
            return $this->exportarRelatorio($request);
        }

        return view('solicitacao::relatorios', [
            'total' => count($chamados),
            'porSituacao' => $porSituacao,
            'porPrioridade' => $porPrioridade,
            'porCategoria' => $porCategoria,
            'porSetor' => $porSetor,
            'mediaFeedback' => $notas !== [] ? round(array_sum($notas) / count($notas), 1) : null,
            'fonte' => $this->fonte(),
            'chamados' => $chamados, // 5.6.5: lista completa para tabela
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'formato' => $formato,
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
        if ($setor !== null) {
            $p['sla_horas'] = SetorChamado::normalizarSla($setor['sla_horas'] ?? SetorChamado::SLA_PADRAO);
        }
        $p['responsavel_id'] = $respId > 0 ? $respId : null;
        $p['responsavel'] = $respNome !== '' ? $respNome : null;
        if ($status === FluxoChamado::STATUS_ABERTO) {
            $p['situacao'] = FluxoChamado::STATUS_TRIAGEM;
        }
        PocRegistro::atualizarPayload($id, $p, meta: ($p['escola'] ?? '').' · '.($p['situacao'] ?? ''));

        $txt = 'Encaminhado para '.$setorNome.($respNome !== '' ? ' / '.$respNome : '');
        if ($setor !== null) {
            $txt .= ' · SLA '.$p['sla_horas'].'h';
        }
        TimelineChamado::andamento($id, $txt, $this->nomeUsuario(), $de, $p['situacao'] ?? $de, $setorNome, $respNome !== '' ? $respNome : null);
        $this->auditar($id, 'encaminhamento', $txt);

        // 5.6.7 / 5.6.15: Notificar técnico responsável
        if ($respId > 0 && class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            $chamado = $reg['payload'];
            $assunto = (string) ($chamado['assunto'] ?? "Chamado #{$id}");
            $escola = (string) ($chamado['escola'] ?? '');
            \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::paraUsuario(
                $respId,
                "Chamado #{$id} encaminhado para você: {$assunto} ({$escola})",
                '/solicitacao-servicos/chamado/'.$id
            );
        }

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

        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class)) {
            $msg = "Chamado #{$id}: {$texto}";
            $link = '/solicitacao-servicos/chamado/'.$id;
            $N = \iEducar\Packages\BuritiLagunaAmbiente\Support\Notificador::class;
            $ids = array_values(array_unique(array_merge(
                $N::usuariosDoProcesso(Processos::ABERTOS),
                $N::usuariosDoProcesso(Processos::DEFERIR),
            )));
            $N::paraUsuarios($ids, $msg, $link);
        }
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
     * @param  list<array{user_id:int,nome:string}>  $anteriores
     * @return list<array{user_id:int,nome:string}>
     */
    private function responsaveisDoRequest(Request $request, array $anteriores = []): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $request->input('responsavel_ids', [])),
            static fn (int $id): bool => $id > 0
        )));
        $porId = [];
        foreach ($this->usuariosRede() as $u) {
            $porId[(int) $u['id']] = (string) $u['nome'];
        }
        foreach ($anteriores as $ant) {
            $uid = (int) ($ant['user_id'] ?? 0);
            if ($uid > 0 && ! isset($porId[$uid])) {
                $porId[$uid] = (string) ($ant['nome'] ?? ('Usuário #'.$uid));
            }
        }
        $lista = [];
        foreach ($ids as $uid) {
            if (! isset($porId[$uid])) {
                continue;
            }
            $lista[] = ['user_id' => $uid, 'nome' => $porId[$uid]];
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

    /**
     * Elegíveis do módulo + já alocados no setor (mesmo se perderam permissão).
     *
     * @param  list<array{user_id:int,nome:string}>  $alocados
     * @return list<array{id:int,nome:string,meta?:string,login?:string}>
     */
    private function usuariosParaSetor(array $alocados = []): array
    {
        $porId = [];
        foreach ($this->usuariosRede() as $u) {
            $porId[(int) $u['id']] = [
                'id' => (int) $u['id'],
                'nome' => (string) $u['nome'],
                'meta' => (string) ($u['meta'] ?? $u['login'] ?? 'Com permissão no módulo'),
                'login' => (string) ($u['login'] ?? ''),
            ];
        }
        foreach ($alocados as $r) {
            $uid = (int) ($r['user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            if (! isset($porId[$uid])) {
                $porId[$uid] = [
                    'id' => $uid,
                    'nome' => (string) ($r['nome'] ?? 'Usuário #'.$uid),
                    'meta' => 'Já alocado · revise a permissão do tipo de usuário',
                    'login' => '',
                ];
            }
        }
        $out = array_values($porId);
        usort($out, static fn (array $a, array $b): int => strcasecmp($a['nome'], $b['nome']));

        return $out;
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
            $slaPorSetor = [];
            foreach (SetorChamado::listar(false) as $s) {
                $slaPorSetor[(int) $s['id']] = (int) $s['sla_horas'];
            }
            $out = [];
            foreach (PocRegistro::listar('chamado') as $r) {
                $p = $r['payload'];
                $situacao = (string) ($p['situacao'] ?? (isset($p['resolvido_em']) ? FluxoChamado::STATUS_RESOLVIDO : FluxoChamado::STATUS_ABERTO));
                if ($situacao === 'deferido') {
                    $situacao = FluxoChamado::STATUS_RESOLVIDO;
                }
                $setorId = isset($p['setor_id']) ? (int) $p['setor_id'] : null;
                $slaHoras = (int) ($p['sla_horas'] ?? 0);
                if ($slaHoras <= 0 && $setorId !== null && isset($slaPorSetor[$setorId])) {
                    $slaHoras = $slaPorSetor[$setorId];
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
                    'setor_id' => $setorId,
                    'setor' => isset($p['setor']) ? (string) $p['setor'] : null,
                    'sla_horas' => $slaHoras > 0 ? $slaHoras : null,
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

    /**
     * 5.6.5: Filtrar chamados por período
     * @return list<array<string,mixed>>
     */
    private function chamadosPorPeriodo(string $dataInicio, string $dataFim): array
    {
        $todos = $this->chamados();
        if ($dataInicio === '' && $dataFim === '') {
            return $todos;
        }

        $filtrados = [];
        foreach ($todos as $c) {
            $abertoEm = (string) ($c['aberto_em'] ?? '');
            if ($abertoEm === '') {
                continue;
            }
            
            try {
                $data = new \DateTime($abertoEm);
                $dataStr = $data->format('Y-m-d');
                
                if ($dataInicio !== '' && $dataStr < $dataInicio) {
                    continue;
                }
                if ($dataFim !== '' && $dataStr > $dataFim) {
                    continue;
                }
                $filtrados[] = $c;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $filtrados;
    }

    /**
     * 5.6.5: Exportação via exportação unificada (dados do módulo → geração 4.x).
     */
    public function exportarRelatorio(Request $request): Response|RedirectResponse
    {
        $this->exigir(Processos::RELATORIOS);

        $dataInicio = trim((string) $request->input('data_inicio', $request->query('data_inicio', '')));
        $dataFim = trim((string) $request->input('data_fim', $request->query('data_fim', '')));
        $formato = strtolower(trim((string) $request->input('formato', $request->query('formato', 'pdf'))));
        $item = strtolower(trim((string) $request->input('item', 'completo')));
        $chamados = $this->chamadosPorPeriodo($dataInicio, $dataFim);

        $periodo = ($dataInicio !== '' || $dataFim !== '')
            ? sprintf('%s a %s', $dataInicio ?: '…', $dataFim ?: '…')
            : 'todos os períodos';

        if (in_array($item, ['situacao', 'prioridade', 'categoria'], true)) {
            $campo = match ($item) {
                'prioridade' => 'prioridade',
                'categoria' => 'categoria',
                default => 'situacao',
            };
            $totais = [];
            foreach ($chamados as $c) {
                $chave = (string) ($c[$campo] ?? '—');
                if ($campo === 'situacao') {
                    $chave = FluxoChamado::rotulo((string) ($c['situacao'] ?? 'aberto'));
                }
                $totais[$chave] = ($totais[$chave] ?? 0) + 1;
            }
            ksort($totais, SORT_STRING);
            $linhas = [];
            foreach ($totais as $rotulo => $qtd) {
                $linhas[] = [$rotulo, $qtd];
            }

            return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
                'Chamados — por '.$item,
                [ucfirst($item), 'Quantidade'],
                $linhas,
                'Solicitação de serviços',
                $formato,
                $periodo,
                null,
                'chamados-'.$item,
            );
        }

        $linhas = [];
        foreach ($chamados as $c) {
            $abertoEm = '';
            try {
                if (! empty($c['aberto_em'])) {
                    $abertoEm = (new \DateTime((string) $c['aberto_em']))->format('d/m/Y H:i');
                }
            } catch (\Throwable) {
                $abertoEm = (string) ($c['aberto_em'] ?? '');
            }
            $linhas[] = [
                $c['id'] ?? '',
                $abertoEm,
                $c['escola'] ?? '',
                $c['assunto'] ?? '',
                $c['categoria'] ?? '',
                $c['prioridade'] ?? '',
                FluxoChamado::rotulo((string) ($c['situacao'] ?? 'aberto')),
                $c['setor'] ?? 'sem setor',
                $c['responsavel'] ?? 'sem responsável',
            ];
        }

        return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
            'Chamados — relatório por período',
            ['ID', 'Data abertura', 'Escola', 'Assunto', 'Categoria', 'Prioridade', 'Situação', 'Setor', 'Responsável'],
            $linhas,
            'Solicitação de serviços',
            $formato,
            $periodo,
            null,
            'chamados-periodo',
        );
    }

    /**
     * 5.6.6: Verifica se usuário pode abrir chamado na categoria
     */
    private function podeAbrirCategoria(string $categoria): bool
    {
        // Config: restricoes_categoria => ['categoria' => ['user_id', 'user_id', ...]]
        $restricoes = config('solicitacao.restricoes_categoria', []);
        
        if (!isset($restricoes[$categoria])) {
            return true; // Sem restrição = todos podem abrir
        }

        $userId = Auth::id();
        if ($userId === null) {
            return false;
        }

        return in_array($userId, (array) $restricoes[$categoria], true);
    }

    /**
     * 5.6.8: Conta chamados abertos do solicitante ou escola
     */
    private function contarChamadosAbertos(?int $userId = null, ?string $escola = null): int
    {
        $count = 0;
        foreach ($this->chamados() as $c) {
            $situacao = (string) ($c['situacao'] ?? 'aberto');
            if (!FluxoChamado::estaAberto($situacao)) {
                continue;
            }

            if ($userId !== null && isset($c['solicitante_id'])) {
                if ((int) ($c['solicitante_id']) === $userId) {
                    $count++;
                }
            }

            if ($escola !== null && isset($c['escola'])) {
                if ((string) $c['escola'] === $escola) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * 5.6.8: Verifica limite de chamados abertos
     */
    private function verificarLimiteChamados(int $userId, string $escola): ?string
    {
        $limitePorUsuario = (int) config('solicitacao.limite_chamados_por_usuario', 0);
        $limitePorEscola = (int) config('solicitacao.limite_chamados_por_escola', 0);

        if ($limitePorUsuario > 0) {
            $abertosUsuario = $this->contarChamadosAbertos($userId, null);
            if ($abertosUsuario >= $limitePorUsuario) {
                return "Você já possui {$abertosUsuario} chamado(s) aberto(s). Limite máximo: {$limitePorUsuario}.";
            }
        }

        if ($limitePorEscola > 0) {
            $abertosEscola = $this->contarChamadosAbertos(null, $escola);
            if ($abertosEscola >= $limitePorEscola) {
                return "A escola {$escola} já possui {$abertosEscola} chamado(s) aberto(s). Limite máximo: {$limitePorEscola}.";
            }
        }

        return null;
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
