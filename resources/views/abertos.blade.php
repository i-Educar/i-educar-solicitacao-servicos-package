@extends('solicitacao::layout')
@section('titulo', 'Pedidos em andamento')
@section('migalha', 'Em andamento')
@section('icone-tela', 'fa-folder-open')
@section('titulo-tela', 'Pedidos em andamento')
@section('lead')
    Filtre por setor, responsável ou situação e abra o pedido para responder ou encaminhar.
@endsection
@section('acoes-topo')
    <a class="botao" href="{{ url('/solicitacao-servicos/novo') }}"><i class="fa fa-plus"></i> Novo pedido</a>
    <a class="botao claro" href="{{ url('/solicitacao-servicos') }}"><i class="fa fa-dashboard"></i> Início</a>
@endsection

@section('conteudo')
@php $f = $filtros ?? ['setor'=>'','responsavel'=>'','status'=>'']; @endphp

    <div class="bloco">
        <h2><i class="fa fa-filter"></i> Filtrar pedidos</h2>
        <form class="form-grid" method="get" action="{{ url('/solicitacao-servicos/abertos') }}">
            @include('buriti-ui::select-busca', [
                'campo' => 'setor',
                'rotulo' => 'Setor',
                'icone' => 'fa-sitemap',
                'opcoes' => collect($setoresFiltro ?? [])->map(fn ($s) => [
                    'id' => $s,
                    'nome' => $s,
                ])->all(),
                'selecionado' => $f['setor'] ?? '',
                'placeholder' => 'Digite o nome do setor…',
                'vazio' => '— todos os setores —',
                'tamanho' => 5,
                'ajuda' => 'Deixe vazio para ver todos. Também pode digitar parte do nome.',
            ])
            <label>Responsável
                <input name="responsavel" value="{{ $f['responsavel'] ?? '' }}" placeholder="Quem está atendendo">
            </label>
            @include('buriti-ui::select-busca', [
                'campo' => 'status',
                'rotulo' => 'Situação',
                'icone' => 'fa-flag',
                'opcoes' => collect($statuses ?? [])->map(fn ($st) => [
                    'id' => $st,
                    'nome' => \iEducar\Packages\BuritiSolicitacaoServicos\Services\FluxoChamado::rotulo($st),
                ])->all(),
                'selecionado' => $f['status'] ?? '',
                'placeholder' => 'Buscar situação…',
                'vazio' => '— em aberto / resolvidos recentes —',
                'tamanho' => 5,
            ])
            <div class="acoes">
                <button class="botao" type="submit"><i class="fa fa-search"></i> Filtrar</button>
                <a class="botao claro" href="{{ url('/solicitacao-servicos/abertos') }}">Limpar</a>
            </div>
        </form>
    </div>

    <div class="bloco">
        @if(!empty($linhas))
            <div class="card-grid">
                @foreach($linhas as $i => $l)
                    <article class="card" style="animation-delay: {{ $i * 0.04 }}s">
                        <header>
                            <span class="ico"><i class="fa fa-ticket"></i></span>
                            <div>
                                <h3>{{ $l['titulo'] ?? ('Item '.($i+1)) }}</h3>
                                <p class="meta">{{ $l['meta'] ?? '' }}</p>
                            </div>
                        </header>
                        @if(!empty($l['badge']))
                            <span class="badge {{ $l['badge_tom'] ?? 'ok' }}">{{ $l['badge'] }}</span>
                        @endif
                        <p style="margin:8px 0 0;color:var(--suave)">{{ $l['corpo'] ?? '' }}</p>
                        @if(!empty($l['url']))
                            <p style="margin:10px 0 0"><a class="botao claro" href="{{ $l['url'] }}"><i class="fa fa-comments"></i> Abrir pedido</a></p>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <p style="color:var(--suave);margin:0">Nenhum pedido com esses filtros.</p>
        @endif
    </div>
@endsection
