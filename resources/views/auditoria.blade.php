@extends('solicitacao::layout')
@section('titulo', 'Auditoria')
@section('migalha', 'Auditoria')
@section('icone-tela', 'fa-history')
@section('titulo-tela', 'Histórico de decisões')
@section('lead')
    Busque o histórico por quem registrou a decisão
@endsection

@section('conteudo')
    <div class="bloco">
        <h2><i class="fa fa-filter"></i> Filtro</h2>
        <form class="form-grid" method="get" action="{{ url('/solicitacao-servicos/auditoria') }}">
            <label>Usuário / atendente <input name="usuario" value="{{ $filtroUsuario ?? '' }}" placeholder="Quem atuou"></label>
            <div class="acoes">
                <button class="botao" type="submit"><i class="fa fa-search"></i> Buscar</button>
                <a class="botao claro" href="{{ url('/solicitacao-servicos/auditoria') }}">Limpar</a>
            </div>
        </form>
    </div>
    <div class="bloco">
        <table>
            <thead><tr><th>Quando</th><th>Ação</th><th>Chamado</th><th>Usuário</th><th>Detalhe</th></tr></thead>
            <tbody>
            @forelse($eventos as $e)
                <tr>
                    <td>{{ $e['quando'] }}</td>
                    <td><span class="badge ok">{{ $e['acao'] }}</span></td>
                    <td>
                        @if($e['chamado_id'])
                            <a href="{{ url('/solicitacao-servicos/chamado/'.$e['chamado_id']) }}">#{{ $e['chamado_id'] }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $e['usuario'] }}</td>
                    <td>{{ $e['detalhe'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum evento de auditoria ainda.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
