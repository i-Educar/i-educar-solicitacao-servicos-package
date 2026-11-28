@extends('solicitacao::layout')
@section('titulo', 'Abrir chamado')
@section('migalha', 'Novo chamado')
@section('icone-tela', 'fa-plus')
@section('titulo-tela', 'Abrir chamado')
@section('lead')
    Balcão escola ↔ SME: identifique a unidade, classifique o pedido e descreva o que precisa.
@endsection
@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/solicitacao-servicos') }}" title="Painel" aria-label="Painel"><i aria-hidden="true" class="fa fa-dashboard"></i></a>
    <a class="botao claro icone" href="{{ url('/solicitacao-servicos/abertos') }}" title="Em andamento" aria-label="Em andamento"><i aria-hidden="true" class="fa fa-list"></i></a>
@endsection

@section('conteudo')
@php
    $rotuloPrioridade = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
    $dicaPrioridade = [
        'alta' => 'Urgente — risco, interrupção ou prazo curto',
        'media' => 'Normal — atendimento na fila do setor',
        'baixa' => 'Pode aguardar — melhoria ou rotina',
    ];
    $rotuloCategoria = [
        'manutencao' => 'Manutenção predial',
        'ti' => 'TI / Informática',
        'geral' => 'Geral / outros',
        'limpeza' => 'Limpeza',
        'transporte' => 'Transporte',
        'alimentacao' => 'Alimentação',
        'pedagogico' => 'Pedagógico',
    ];
    $escolas = $escolas ?? [];
    $categorias = $categorias ?? [];
    $setores = $setores ?? [];
    $prioridades = $prioridades ?? ['alta', 'media', 'baixa'];
    $slaHoras = $slaHoras ?? ['alta' => 24, 'media' => 72, 'baixa' => 120];
    $prioAtual = old('prioridade', 'media');
    $catAtual = old('categoria', ($categorias[0]['nome'] ?? 'geral'));
@endphp

<style>
    .chamado-novo { display: grid; gap: 14px; max-width: 880px; }
    .chamado-passos {
        display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 4px; padding: 0; list-style: none;
    }
    .chamado-passos li {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 7px 12px; border-radius: 999px; font-size: .78rem; font-weight: 700;
        background: color-mix(in srgb, var(--clara) 55%, #fff); color: var(--suave);
        border: 1px solid var(--linha);
    }
    .chamado-passos li i { opacity: .75; }
    .chamado-passos li.ativo {
        background: color-mix(in srgb, var(--cor-tela) 14%, #fff);
        color: var(--cor-tela); border-color: color-mix(in srgb, var(--cor-tela) 35%, var(--linha));
    }
    .chamado-secao {
        display: grid; gap: 12px; padding: 14px 14px 16px;
        border: 1px solid var(--linha); border-radius: 14px;
        background: linear-gradient(180deg, color-mix(in srgb, var(--clara) 35%, #fff), #fff);
    }
    .chamado-secao h2 {
        margin: 0; font-size: .95rem; display: flex; align-items: center; gap: 8px;
    }
    .chamado-secao h2 .num {
        width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center;
        background: linear-gradient(145deg, var(--destaque), var(--cor-tela)); color: #fff;
        font-size: .78rem; font-weight: 800;
    }
    .chamado-secao p.ajuda-secao {
        margin: -4px 0 0; color: var(--suave); font-size: .82rem; font-weight: 500;
    }
    .chamado-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: start;
    }
    .chamado-grid .col-wide { grid-column: 1 / -1; }
    @media (max-width: 720px) {
        .chamado-grid { grid-template-columns: 1fr; }
    }
    /* Evita listbox aberto / sobreposição */
    .chamado-novo .buriti-select,
    .chamado-novo .buriti-select--compacto {
        width: 100%; max-width: none; margin: 0; align-self: start;
    }
    .chamado-novo .buriti-select-lista[size] {
        height: 36px !important; min-height: 36px !important; size: unset;
    }
    .chamado-novo .form-campo {
        display: grid; gap: 4px; font-size: .8rem; font-weight: 700; color: var(--suave); margin: 0;
    }
    .chamado-novo .form-campo > span {
        display: inline-flex; align-items: center; gap: 6px; color: var(--tinta); font-weight: 800;
    }
    .chamado-novo .form-campo > span i { color: var(--cor-tela); }
    .chamado-novo .form-campo input,
    .chamado-novo .form-campo select,
    .chamado-novo .form-campo textarea {
        min-height: 40px; padding: 8px 10px; border: 1px solid var(--linha);
        border-radius: 10px; font: inherit; font-weight: 500; background: #fff; color: var(--tinta);
    }
    .chamado-novo .form-campo textarea { min-height: 110px; resize: vertical; }
    .chamado-novo .form-campo small { font-weight: 500; color: var(--suave); }
    .prio-grade {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
    }
    @media (max-width: 560px) { .prio-grade { grid-template-columns: 1fr; } }
    .prio-opcao { margin: 0; cursor: pointer; }
    .prio-opcao input { position: absolute; opacity: 0; pointer-events: none; }
    .prio-opcao .prio-card {
        display: grid; gap: 4px; padding: 12px; border-radius: 12px; min-height: 88px;
        border: 1px solid var(--linha); background: #fff; transition: border-color .15s, box-shadow .15s;
    }
    .prio-opcao .prio-card strong { font-size: .95rem; color: var(--tinta); }
    .prio-opcao .prio-card span { font-size: .75rem; color: var(--suave); font-weight: 500; line-height: 1.3; }
    .prio-opcao .prio-card em {
        font-style: normal; font-size: .72rem; font-weight: 800; letter-spacing: .04em;
        text-transform: uppercase; color: var(--cor-tela);
    }
    .prio-opcao input:checked + .prio-card {
        border-color: var(--cor-tela);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--cor-tela) 18%, transparent);
        background: color-mix(in srgb, var(--clara) 55%, #fff);
    }
    .prio-opcao.alta input:checked + .prio-card { border-color: var(--erro); box-shadow: 0 0 0 3px color-mix(in srgb, var(--erro) 18%, transparent); }
    .prio-opcao.alta .prio-card em { color: var(--erro); }
    .prio-opcao.baixa .prio-card em { color: var(--ok); }
    .chamado-acoes {
        display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px;
        padding-top: 4px; border-top: 1px solid var(--linha); margin-top: 4px;
    }
</style>

@if(empty($ativo))
    <div class="bloco">
        <div class="buriti-alerta aviso">
            <i class="fa fa-info-circle"></i>
            <div>
                <strong>Abertura desligada</strong>
                <p>A abertura de novos chamados está desabilitada na configuração do módulo.</p>
            </div>
        </div>
    </div>
@else
    <div class="bloco">
        <ol class="chamado-passos" aria-label="Etapas do chamado">
            <li class="ativo"><i class="fa fa-building"></i> 1 · Unidade</li>
            <li class="ativo"><i class="fa fa-tags"></i> 2 · Classificação</li>
            <li class="ativo"><i class="fa fa-pencil"></i> 3 · Detalhes</li>
            <li><i class="fa fa-paper-plane"></i> 4 · Enviar</li>
        </ol>

        <form class="chamado-novo" method="post" action="{{ url('/solicitacao-servicos/novo') }}" enctype="multipart/form-data" novalidate>
            @csrf

            <section class="chamado-secao" aria-labelledby="sec-unidade">
                <h2 id="sec-unidade"><span class="num">1</span> Unidade solicitante</h2>
                <p class="ajuda-secao">Escola da rede que precisa do serviço.</p>
                <div class="chamado-grid">
                    @include('buriti-ui::select-busca', [
                        'campo' => 'escola',
                        'rotulo' => 'Escola',
                        'icone' => 'fa-building',
                        'opcoes' => collect($escolas)->map(fn ($e) => [
                            'id' => $e['nome'],
                            'nome' => $e['nome'],
                            'meta' => ! empty($e['cod']) ? 'cód. '.$e['cod'] : '',
                        ])->all(),
                        'selecionado' => old('escola'),
                        'placeholder' => 'Digite para filtrar a escola…',
                        'vazio' => '— escolher escola —',
                        'obrigatorio' => true,
                        'compacto' => true,
                        'colWide' => true,
                        'ajuda' => count($escolas) === 0
                            ? 'Nenhuma escola encontrada no cadastro da rede.'
                            : count($escolas).' escola(s) disponíveis.',
                    ])
                </div>
            </section>

            <section class="chamado-secao" aria-labelledby="sec-class">
                <h2 id="sec-class"><span class="num">2</span> Classificação</h2>
                <p class="ajuda-secao">Tipo, urgência e setor responsável (ou triagem da SME).</p>

                <div class="chamado-grid">
                    <label class="form-campo">
                        <span><i class="fa fa-tags"></i> Tipo de pedido *</span>
                        <select name="categoria" required>
                            <option value="">— escolher tipo —</option>
                            @forelse($categorias as $c)
                                @php $nome = (string) ($c['nome'] ?? ''); @endphp
                                <option value="{{ $nome }}" @selected((string) $catAtual === $nome)>
                                    {{ $rotuloCategoria[$nome] ?? ucfirst(str_replace('_', ' ', $nome)) }}
                                    @if(!empty($c['sla_horas'])) · prazo {{ $c['sla_horas'] }}h @endif
                                </option>
                            @empty
                                <option value="geral" @selected($catAtual === 'geral')>Geral / outros</option>
                            @endforelse
                        </select>
                        <small>Cadastre tipos em «Tipos de pedido» se a lista estiver incompleta.</small>
                    </label>

                    @include('buriti-ui::select-busca', [
                        'campo' => 'setor_id',
                        'rotulo' => 'Setor que vai atender',
                        'icone' => 'fa-sitemap',
                        'opcoes' => collect($setores)
                            ->filter(fn ($s) => ($s['id'] ?? 0) > 0 && ! empty($s['ativo'] ?? true))
                            ->map(fn ($s) => [
                                'id' => $s['id'],
                                'nome' => $s['nome'],
                                'meta' => count($s['responsaveis'] ?? []).' responsável(is)',
                            ])->values()->all(),
                        'selecionado' => old('setor_id', 0),
                        'placeholder' => 'Filtrar setor…',
                        'vazio' => '— triagem da SME (sem setor ainda) —',
                        'compacto' => true,
                        'ajuda' => 'Se não souber o setor, deixe em triagem — a equipe encaminha depois.',
                    ])
                </div>

                <div class="form-campo col-wide">
                    <span><i class="fa fa-bolt"></i> Urgência *</span>
                    <div class="prio-grade" role="radiogroup" aria-label="Urgência do chamado">
                        @foreach($prioridades as $p)
                            <label class="prio-opcao {{ $p }}">
                                <input type="radio" name="prioridade" value="{{ $p }}" @checked((string) $prioAtual === (string) $p) required>
                                <span class="prio-card">
                                    <em>{{ $rotuloPrioridade[$p] ?? ucfirst($p) }}</em>
                                    <strong>SLA {{ $slaHoras[$p] ?? '—' }}h</strong>
                                    <span>{{ $dicaPrioridade[$p] ?? '' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="chamado-secao" aria-labelledby="sec-det">
                <h2 id="sec-det"><span class="num">3</span> Detalhes do chamado</h2>
                <p class="ajuda-secao">Assunto claro e descrição objetiva ajudam o setor a responder mais rápido.</p>

                <div class="chamado-grid">
                    <label class="form-campo col-wide">
                        <span><i class="fa fa-header"></i> Assunto *</span>
                        <input name="assunto" required maxlength="180" value="{{ old('assunto') }}"
                               placeholder="Ex.: Vazamento no banheiro feminino do pavimento térreo">
                    </label>
                    <label class="form-campo col-wide">
                        <span><i class="fa fa-align-left"></i> Descrição</span>
                        <textarea name="descricao" placeholder="Local, impacto na escola, o que já foi tentado…">{{ old('descricao') }}</textarea>
                        <small>Opcional, mas recomendado para triagem.</small>
                    </label>
                    <div class="col-wide">
                        @include('buriti-ui::campo-anexo', [
                            'campo' => 'anexo',
                            'rotulo' => 'Anexo PDF (opcional)',
                            'tituloAcao' => 'Soltar o PDF aqui',
                            'ajuda' => 'Foto ou laudo? Envie em PDF — máx. '.($anexoMaxKb ?? 5120).' KB.',
                            'accept' => 'application/pdf,.pdf',
                            'icone' => 'fa-file-pdf-o',
                        ])
                    </div>
                </div>
            </section>

            <div class="chamado-acoes">
                <a class="botao claro" href="{{ url('/solicitacao-servicos/abertos') }}">Cancelar</a>
                <button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Abrir chamado</button>
            </div>
        </form>
    </div>
@endif
@endsection
