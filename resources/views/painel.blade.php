@extends('solicitacao::layout')
@section('titulo', 'Início — Pedidos de serviço')
@section('migalha', 'Início')
@section('icone-tela', 'fa-ticket')
@section('titulo-tela', 'Pedidos de serviço da rede')
@section('lead')
    Abra um pedido, encaminhe ao setor certo e acompanhe o prazo de atendimento.
@endsection
@section('acoes-topo')
    <a class="botao icone" href="{{ url('/solicitacao-servicos/novo') }}" title="Novo pedido" aria-label="Novo pedido"><i aria-hidden="true" class="fa fa-plus"></i></a>
    <a class="botao claro icone" href="{{ url('/solicitacao-servicos/abertos') }}" title="Em andamento" aria-label="Em andamento"><i aria-hidden="true" class="fa fa-comments"></i></a>
@endsection

@section('conteudo')
    @include('buriti-ui::painel-corpo', [
        'kpis' => $kpis ?? [],
        'atalhos' => $atalhos ?? [],
        'destaques' => $destaques ?? [],
        'analise' => $analise ?? [],
        'analiseTitulo' => $analiseTitulo ?? 'Análise',
        'resumo' => $resumo ?? null,
        'fluxoTitulo' => $fluxoTitulo ?? 'Fluxo do módulo',
    ])
@endsection
