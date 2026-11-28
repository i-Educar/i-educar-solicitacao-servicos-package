@extends('solicitacao::layout')
@php
    $status = $chamado['situacao'] ?? 'aberto';
    $rotulo = $rotulosStatus[$status] ?? ucfirst($status);
    $prio = (string) ($chamado['prioridade'] ?? 'media');
    $prioRotulo = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'][$prio] ?? $prio;
    $euNome = auth()->user()?->name ?? auth()->user()?->login ?? '';
    $ordemStatus = [
        'aberto' => 0,
        'triagem' => 1,
        'em_atendimento' => 2,
        'aguardando' => 2,
        'resolvido' => 3,
        'fechado' => 4,
        'indeferido' => 4,
        'cancelado' => 4,
    ];
    $atualIdx = $ordemStatus[$status] ?? 0;
    $etapas = [
        ['chave' => 'aberto', 'rotulo' => 'Protocolo registrado', 'detalhe' => 'Pedido recebido pela central de serviços.'],
        ['chave' => 'triagem', 'rotulo' => 'Triagem', 'detalhe' => 'Classificação de urgência e setor responsável.'],
        ['chave' => 'em_atendimento', 'rotulo' => 'Em atendimento', 'detalhe' => 'Equipe analisa e responde à escola.'],
        ['chave' => 'encerrado', 'rotulo' => 'Encerramento', 'detalhe' => 'Resolução, indeferimento ou cancelamento.'],
    ];
    foreach ($etapas as $i => &$etapa) {
        $etapa['feito'] = $i < $atualIdx || ($atualIdx >= 3 && $i === 3);
        $etapa['atual'] = ($i === min($atualIdx, 3));
        if ($i === 3 && in_array($status, ['resolvido', 'fechado', 'indeferido', 'cancelado'], true)) {
            $etapa['detalhe'] = $rotulo;
            $etapa['feito'] = true;
            $etapa['atual'] = true;
        }
    }
    unset($etapa);
@endphp
@section('titulo', 'Pedido #'.$chamado['id'])
@section('migalha', 'Pedido #'.$chamado['id'])
@section('icone-tela', 'fa-file-text-o')
@section('titulo-tela', 'Protocolo do pedido #'.$chamado['id'])
@section('lead')
    {{ $chamado['assunto'] }} · {{ $chamado['escola'] }}
@endsection
@section('acoes-topo')
    @if(!empty($podeEncaminhar))
        <form method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/assumir') }}" style="display:inline">@csrf
            <button class="botao primaria icone" type="submit" title="Assumir" aria-label="Assumir"><i aria-hidden="true" class="fa fa-hand-paper-o"></i></button>
        </form>
        <button type="button" class="botao icone" data-buriti-modal="modal-encaminhar" title="Encaminhar" aria-label="Encaminhar"><i aria-hidden="true" class="fa fa-share"></i></button>
    @endif
    @if(!empty($proximos))
        <button type="button" class="botao consulta icone" data-buriti-modal="modal-status" title="Situação" aria-label="Situação"><i aria-hidden="true" class="fa fa-flag"></i></button>
    @endif
    @if(!empty($podeDeferir) || !empty($podeIndeferir))
        <button type="button" class="botao icone" data-buriti-modal="modal-encerrar" title="Encerrar" aria-label="Encerrar"><i aria-hidden="true" class="fa fa-handshake-o"></i></button>
    @endif
    @if(!empty($podeFeedback))
        <button type="button" class="botao claro icone" data-buriti-modal="modal-feedback" title="Avaliar" aria-label="Avaliar"><i aria-hidden="true" class="fa fa-star"></i></button>
    @endif
    <a class="botao claro icone" href="{{ url('/solicitacao-servicos/abertos') }}" title="Pedidos" aria-label="Pedidos"><i aria-hidden="true" class="fa fa-folder-open"></i></a>
@endsection

@section('foco')
<section class="buriti-foco">
    <p class="buriti-foco-kicker">Pedido #{{ $chamado['id'] }}</p>
    <h2 class="buriti-foco-titulo">{{ $chamado['assunto'] }}</h2>
    <p class="buriti-foco-texto">{{ $chamado['escola'] }} · {{ $rotulo }} · urgência {{ $prioRotulo }}</p>
</section>
@endsection

@section('conteudo')
    @include('buriti-ui::setor-contato', [
        'setor' => class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::class)
            ? \iEducar\Packages\BuritiLagunaAmbiente\Support\SetorResponsavel::de('solicitacao')
            : null,
        'titulo' => 'Setor responsável pelo atendimento',
    ])

    <div class="bloco">
        <p>
            <span class="badge {{ \iEducar\Packages\BuritiSolicitacaoServicos\Services\FluxoChamado::tom($status) }}">{{ $rotulo }}</span>
            <span class="badge warn">Urgência {{ $prioRotulo }}</span>
            @if(!empty($chamado['setor']))
                <span class="badge warn"><i class="fa fa-sitemap"></i> {{ $chamado['setor'] }}</span>
            @endif
            @if(!empty($chamado['responsavel']))
                <span class="badge ok"><i class="fa fa-user"></i> {{ $chamado['responsavel'] }}</span>
            @endif
        </p>
        <dl class="form-grid" style="margin-top:12px">
            <div><strong>Aberto em</strong><br>{{ $chamado['aberto_em'] }}</div>
            <div><strong>Quem pediu</strong><br>{{ $chamado['solicitante'] ?? '—' }}</div>
            <div><strong>Tipo</strong><br>{{ $chamado['categoria'] ?? 'Geral' }}</div>
            <div><strong>Escola</strong><br>{{ $chamado['escola'] }}</div>
            @if(!empty($chamado['descricao']))
                <div class="col-wide"><strong>Detalhes do pedido</strong><br>{{ $chamado['descricao'] }}</div>
            @endif
            @if(!empty($chamado['parecer']))
                <div class="col-wide"><strong>Como ficou</strong><br>{{ $chamado['parecer'] }}</div>
            @endif
            @if(!empty($chamado['feedback']))
                <div class="col-wide"><strong>Avaliação de quem pediu</strong><br>
                    {{ str_repeat('★', (int)($chamado['feedback']['nota'] ?? 0)) }}{{ str_repeat('☆', 5 - (int)($chamado['feedback']['nota'] ?? 0)) }}
                    @if(!empty($chamado['feedback']['comentario'])) — {{ $chamado['feedback']['comentario'] }}@endif
                </div>
            @endif
        </dl>
        @if(!empty($anexos))
            <p style="margin:14px 0 6px;font-weight:800"><i class="fa fa-paperclip"></i> Documentos anexados</p>
            <ul class="buriti-anexo-lista">
                @foreach($anexos as $a)
                    <li>
                        <a href="{{ url('/solicitacao-servicos/anexo/'.$a['id']) }}">
                            <i class="fa fa-file-pdf-o"></i>
                            {{ $a['nome'] }}
                            <small style="font-weight:500;color:var(--suave)">({{ number_format(($a['tamanho'] ?? 0)/1024, 0) }} KB)</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @include('buriti-ui::protocolo-etapas', [
        'titulo' => 'Andamento institucional',
        'etapas' => $etapas,
    ])

    <div class="bloco">
        <h2 style="margin:0 0 12px;font-size:1.05rem">Trâmites e manifestações</h2>
        @if(empty($timeline))
            <p style="margin:0;color:var(--suave)">Ainda não há manifestações registradas neste protocolo.</p>
        @else
            <ol class="buriti-tramites" style="list-style:none;margin:0;padding:0;display:grid;gap:10px">
                @foreach($timeline as $item)
                    @php
                        $ehMsg = ($item['kind'] ?? '') === 'mensagem';
                        $autor = (string) ($item['autor'] ?? '');
                        $selo = $ehMsg ? 'Manifestação' : 'Registro interno';
                        if (! empty($item['para_status'])) {
                            $selo = 'Atualização de situação';
                        }
                    @endphp
                    <li style="border:1px solid var(--linha,#cfd8df);border-radius:12px;padding:12px 14px;background:#fff">
                        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:6px">
                            <span class="badge">{{ $selo }}</span>
                            <strong style="font-size:.9rem">{{ $autor !== '' ? $autor : 'Equipe' }}</strong>
                            <span style="margin-left:auto;font-size:.78rem;color:var(--suave)">{{ $item['em'] ?: ($item['created_at'] ?? '') }}</span>
                        </div>
                        <p style="margin:0">{{ $item['texto'] }}</p>
                        @if(!empty($item['para_status']))
                            <p style="margin:6px 0 0;color:var(--suave);font-size:.9rem">
                                @if(!empty($item['de_status'])){{ $rotulosStatus[$item['de_status']] ?? $item['de_status'] }} → @endif
                                {{ $rotulosStatus[$item['para_status']] ?? $item['para_status'] }}
                                @if(!empty($item['setor'])) · {{ $item['setor'] }}@endif
                                @if(!empty($item['responsavel'])) · {{ $item['responsavel'] }}@endif
                            </p>
                        @endif
                        @foreach(($item['anexo_ids'] ?? []) as $aid)
                            @if(!empty($anexosPorId[$aid]))
                                <a style="display:inline-flex;gap:6px;margin-top:8px" href="{{ url('/solicitacao-servicos/anexo/'.$aid) }}">
                                    <i class="fa fa-paperclip"></i> {{ $anexosPorId[$aid]['nome'] }}
                                </a>
                            @endif
                        @endforeach
                    </li>
                @endforeach
            </ol>
        @endif

        @if(!empty($podeResponder))
            <div style="margin-top:16px;display:grid;gap:14px">
                <form method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/responder') }}" enctype="multipart/form-data">
                    @csrf
                    <label style="display:block;font-weight:700;margin-bottom:6px">Resposta oficial à escola</label>
                    <textarea name="texto" required placeholder="Registre a manifestação institucional…" rows="3" style="width:100%;padding:10px;border:1px solid var(--linha);border-radius:10px;font:inherit"></textarea>
                    @include('buriti-ui::campo-anexo', [
                        'campo' => 'anexo',
                        'rotulo' => 'Anexar PDF (opcional)',
                        'tituloAcao' => 'Selecionar ou soltar o PDF',
                        'ajuda' => 'Documento oficial em PDF. Máx. '.($anexoMaxKb ?? 5120).' KB.',
                        'accept' => 'application/pdf,.pdf',
                        'icone' => 'fa-file-pdf-o',
                    ])
                    <div class="acoes"><button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Registrar resposta</button></div>
                </form>
                <form method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/andamento') }}">
                    @csrf
                    <label style="display:block;font-weight:700;margin-bottom:6px">Nota interna da equipe</label>
                    <textarea name="texto" required placeholder="Registro interno: diligência, contato com fornecedor, visita…" rows="2" style="width:100%;padding:10px;border:1px solid var(--linha);border-radius:10px;font:inherit"></textarea>
                    <small style="display:block;margin:6px 0 0;color:var(--suave);font-size:.78rem">Visível apenas à equipe — a escola não recebe este texto.</small>
                    <div class="acoes"><button class="botao claro" type="submit"><i class="fa fa-check"></i> Salvar nota</button></div>
                </form>
            </div>
        @endif
    </div>

    @if(!empty($podeEncaminhar))
        @include('buriti-ui::operacao-modal', [
            'id' => 'modal-encaminhar',
            'titulo' => 'Passar para outro setor',
            'lead' => 'Primeiro o setor; o responsável vem da lista daquele setor.',
            'corpo' => view('solicitacao::partials.detalhe-encaminhar', [
                'chamado' => $chamado,
                'setores' => $setores ?? [],
            ])->render(),
        ])
    @endif

    @if(!empty($proximos))
        @php
            $corpoStatus = '<form class="form-grid" method="post" action="'.e(url('/solicitacao-servicos/chamado/'.$chamado['id'].'/status')).'">'
                .csrf_field()
                .'<label>Próxima situação<select name="status" required>';
            foreach ($proximos as $st) {
                $corpoStatus .= '<option value="'.e($st).'">'.e($rotulosStatus[$st] ?? $st).'</option>';
            }
            $corpoStatus .= '</select></label>'
                .'<label class="col-wide">Observação (opcional) <input name="nota" placeholder="Motivo da mudança"></label>'
                .'<div class="acoes"><button class="botao claro" type="submit"><i class="fa fa-check"></i> Atualizar</button></div></form>';
        @endphp
        @include('buriti-ui::operacao-modal', [
            'id' => 'modal-status',
            'titulo' => 'Mudar a situação',
            'lead' => 'Atualize o andamento do pedido.',
            'corpo' => $corpoStatus,
        ])
    @endif

    @if(!empty($podeDeferir) || !empty($podeIndeferir))
        @php
            $corpoEncerrar = '<div class="form-grid">';
            if (! empty($podeDeferir)) {
                $corpoEncerrar .= '<form method="post" action="'.e(url('/solicitacao-servicos/chamado/'.$chamado['id'].'/deferir')).'">'
                    .csrf_field()
                    .'<label class="col-wide">Marcar como resolvido<textarea name="parecer" placeholder="O que foi feito">Resolvido — atendimento concluído.</textarea></label>'
                    .'<div class="acoes"><button class="botao" type="submit"><i class="fa fa-check"></i> Resolver</button></div></form>';
            }
            if (! empty($podeIndeferir)) {
                $corpoEncerrar .= '<form method="post" action="'.e(url('/solicitacao-servicos/chamado/'.$chamado['id'].'/indeferir')).'">'
                    .csrf_field()
                    .'<label class="col-wide">Não atender / indeferir<textarea name="parecer" placeholder="Motivo">Indeferido — fora do escopo.</textarea></label>'
                    .'<div class="acoes"><button class="botao perigo claro" type="submit"><i class="fa fa-times"></i> Indeferir</button></div></form>';
            }
            $corpoEncerrar .= '</div>';
        @endphp
        @include('buriti-ui::operacao-modal', [
            'id' => 'modal-encerrar',
            'titulo' => 'Encerrar o pedido',
            'lead' => 'Resolver ou indeferir — ação definitiva.',
            'corpo' => $corpoEncerrar,
        ])
    @endif

    @if(!empty($podeFeedback))
        @php
            $corpoFb = '<form class="form-grid" method="post" action="'.e(url('/solicitacao-servicos/chamado/'.$chamado['id'].'/feedback')).'">'
                .csrf_field()
                .'<label>Nota<select name="nota" required>';
            $notas = ['','Péssimo','Ruim','Regular','Bom','Ótimo'];
            for ($i = 5; $i >= 1; $i--) {
                $corpoFb .= '<option value="'.$i.'">'.$i.' — '.$notas[$i].'</option>';
            }
            $corpoFb .= '</select></label>'
                .'<label class="col-wide">Comentário <input name="comentario" placeholder="Opcional"></label>'
                .'<div class="acoes"><button class="botao" type="submit"><i class="fa fa-star"></i> Enviar avaliação</button></div></form>';
        @endphp
        @include('buriti-ui::operacao-modal', [
            'id' => 'modal-feedback',
            'titulo' => 'Como foi o atendimento?',
            'lead' => 'Avaliação de quem pediu.',
            'corpo' => $corpoFb,
        ])
    @endif
@endsection
