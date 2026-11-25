@extends('solicitacao::layout')
@section('titulo', 'Relatórios')
@section('migalha', 'Relatórios')
@section('icone-tela', 'fa-bar-chart')
@section('titulo-tela', 'Andamento dos chamados')
@section('lead')
    Situação, setor, prioridade e média de feedback · fonte {{ $fonte }}
@endsection

@section('conteudo')
    <div class="numeros">
        <article><div class="kpi-ico"><i class="fa fa-ticket"></i></div><span>Total</span><strong>{{ $total }}</strong></article>
        @if($mediaFeedback !== null)
            <article style="--tom: var(--destaque)"><div class="kpi-ico"><i class="fa fa-star"></i></div><span>Média feedback</span><strong>{{ $mediaFeedback }}</strong></article>
        @endif
        @foreach($porSituacao as $sit => $qtd)
            <article><div class="kpi-ico"><i class="fa fa-flag"></i></div><span>{{ $sit }}</span><strong>{{ $qtd }}</strong></article>
        @endforeach
    </div>
    <div class="bloco">
        <h2>Por prioridade</h2>
        <table>
            <thead><tr><th>Prioridade</th><th>Qtd</th></tr></thead>
            <tbody>
            @forelse($porPrioridade as $p => $q)
                <tr><td>{{ $p }}</td><td>{{ $q }}</td></tr>
            @empty
                <tr><td colspan="2">Sem dados</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="bloco">
        <h2>Por categoria</h2>
        <table>
            <thead><tr><th>Categoria</th><th>Qtd</th></tr></thead>
            <tbody>
            @forelse($porCategoria as $c => $q)
                <tr><td>{{ $c }}</td><td>{{ $q }}</td></tr>
            @empty
                <tr><td colspan="2">Sem dados</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="bloco">
        <h2>Por setor</h2>
        <table>
            <thead><tr><th>Setor</th><th>Qtd</th></tr></thead>
            <tbody>
            @forelse(($porSetor ?? []) as $s => $q)
                <tr><td>{{ $s }}</td><td>{{ $q }}</td></tr>
            @empty
                <tr><td colspan="2">Sem dados</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
