@extends('solicitacao::layout')
@section('titulo', 'Início — Pedidos de serviço')
@section('migalha', 'Início')
@section('icone-tela', 'fa-ticket')
@section('titulo-tela', 'Pedidos de serviço da rede')
@section('lead')
    Abra um pedido, encaminhe ao setor certo e acompanhe o prazo de atendimento.
@endsection
@section('acoes-topo')
    <a class="botao" href="{{ url('/solicitacao-servicos/novo') }}"><i class="fa fa-plus"></i> Novo pedido</a>
    <a class="botao claro" href="{{ url('/solicitacao-servicos/abertos') }}"><i class="fa fa-comments"></i> Em andamento</a>
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
