@extends('solicitacao::layout')
@section('titulo', 'Categorias')
@section('migalha', 'Categorias')
@section('icone-tela', 'fa-tags')
@section('titulo-tela', 'Categorias de serviço')
@section('lead')
    Cadastro de tipos de chamado com SLA padrão em horas
@endsection

@section('conteudo')
    @if($podeGravar)
        <div class="bloco">
            <h2><i class="fa fa-plus"></i> Nova categoria</h2>
            <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/categorias') }}">
                @csrf
                <label>Nome <input name="nome" required placeholder="Ex.: manutenção"></label>
                <label>SLA (horas) <input type="number" name="sla_horas" min="1" value="48" required></label>
                <div class="acoes"><button class="botao" type="submit">Salvar</button></div>
            </form>
        </div>
    @endif
    <div class="bloco">
        <h2>Categorias</h2>
        <table>
            <thead><tr><th>Nome</th><th>SLA</th></tr></thead>
            <tbody>
            @foreach($categorias as $c)
                <tr><td>{{ $c['nome'] }}</td><td>{{ $c['sla_horas'] }} h</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
