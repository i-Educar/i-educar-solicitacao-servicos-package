@extends('solicitacao::layout')
@section('titulo', 'SLA')
@section('migalha', 'SLA')
@section('icone-tela', 'fa-clock-o')
@section('titulo-tela', 'SLA')
@section('lead')
    Prazos e atrasos
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/solicitacao-servicos') }}"><i class="fa fa-dashboard"></i> Painel</a>
@endsection

@section('conteudo')

    <div class="bloco">
        @yield('extra')
        @if(!empty($linhas))
            <div class="card-grid">
                @foreach($linhas as $i => $l)
                    <article class="card" style="animation-delay: {{ $i * 0.04 }}s">
                        <header>
                            <span class="ico"><i class="fa fa-clock-o"></i></span>
                            <div>
                                <h3>{{ $l['titulo'] ?? ('Item '.($i+1)) }}</h3>
                                <p class="meta">{{ $l['meta'] ?? '' }}</p>
                            </div>
                        </header>
                        @if(!empty($l['badge']))
                            <span class="badge {{ $l['badge_tom'] ?? 'ok' }}">{{ $l['badge'] }}</span>
                        @endif
                        <p style="margin:8px 0 0;color:var(--suave)">{{ $l['corpo'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <p style="color:var(--suave);margin:0">Sem registros nesta PoC — os dados de demonstração aparecem após o povoamento do módulo.</p>
        @endif
    </div>
@endsection
