@extends('solicitacao::layout')
@section('titulo', 'Setores e responsáveis')
@section('migalha', 'Setores')
@section('icone-tela', 'fa-sitemap')
@section('titulo-tela', 'Setores e responsáveis')
@section('lead')
    Organize para onde os chamados vão e quem atende — a lista de pessoas já respeita permissão do módulo e o seu escopo.
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/solicitacao-servicos/novo') }}"><i class="fa fa-plus"></i> Abrir chamado</a>
@endsection

@section('conteudo')
@if(!empty($podeGravar))
    <div class="fluxo-sep">Cadastrar</div>
    <section class="fluxo-bloco acao">
        <header class="fluxo-cabeca">
            <div>
                <h2><i class="fa fa-plus"></i> Novo setor</h2>
                <p>Crie o “departamento” e indique quem responde os chamados.</p>
            </div>
            <span class="selo-fluxo">Novo</span>
        </header>
        <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/setores') }}">
            @csrf
            <label>Nome <input name="nome" required placeholder="Ex.: Manutenção predial"></label>
            <label class="col-wide">Descrição <input name="descricao" placeholder="Escopo do setor"></label>
            @if(!empty($usuarios))
                @include('buriti-ui::select-multi', [
                    'campo' => 'responsavel_ids[]',
                    'rotulo' => 'Responsáveis (com acesso ao módulo)',
                    'icone' => 'fa-users',
                    'opcoes' => collect($usuarios)->map(fn ($u) => [
                        'id' => $u['id'],
                        'nome' => $u['nome'],
                        'meta' => $u['meta'] ?? ($u['login'] ?? ''),
                    ])->all(),
                    'selecionados' => [],
                    'placeholder' => 'Digite o nome ou matrícula…',
                    'ajuda' => 'Só entram pessoas com permissão de setores/responder/encaminhar no seu escopo.',
                    'tamanho' => 6,
                ])
            @else
                <p class="flash warn" style="margin:0">Nenhuma pessoa elegível na lista — verifique menus do tipo de usuário.</p>
            @endif
            <label class="col-wide">Outros nomes (texto livre) <input name="responsaveis_texto" placeholder="Se alguém ainda não está no sistema"></label>
            <div class="acoes"><button class="botao" type="submit"><i class="fa fa-save"></i> Cadastrar setor</button></div>
        </form>
    </section>
@endif

    <div class="fluxo-sep">Setores existentes</div>
    <section class="fluxo-bloco consulta">
        <header class="fluxo-cabeca">
            <div>
                <h2><i class="fa fa-sitemap"></i> Setores</h2>
                <p>Consulta e edição — separado do cadastro acima.</p>
            </div>
            <span class="selo-fluxo">Lista</span>
        </header>
        @if(empty($setores))
            <p style="margin:0;color:var(--suave)">Nenhum setor cadastrado.</p>
        @else
            <div class="card-grid">
                @foreach($setores as $s)
                    <article class="card">
                        <header>
                            <span class="ico"><i class="fa fa-building"></i></span>
                            <div>
                                <h3>{{ $s['nome'] }}</h3>
                                <p class="meta">{{ $s['descricao'] ?: '—' }}</p>
                            </div>
                        </header>
                        <span class="badge {{ !empty($s['ativo']) ? 'ok' : 'fail' }}">{{ !empty($s['ativo']) ? 'Ativo' : 'Inativo' }}</span>
                        <p style="margin:8px 0 0;color:var(--suave);font-size:.9rem">
                            @forelse(($s['responsaveis'] ?? []) as $r)
                                {{ $r['nome'] }}@if(!$loop->last), @endif
                            @empty
                                Sem responsáveis
                            @endforelse
                        </p>
                        @if(!empty($podeGravar) && ($s['id'] ?? 0) > 0)
                            <details style="margin-top:10px">
                                <summary>Editar este setor</summary>
                                <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/setores/'.$s['id']) }}" style="margin-top:8px">
                                    @csrf
                                    <label>Nome <input name="nome" value="{{ $s['nome'] }}" required></label>
                                    <label class="col-wide">Descrição <input name="descricao" value="{{ $s['descricao'] }}"></label>
                                    <label>Ativo
                                        <select name="ativo"><option value="1" @selected($s['ativo'])>Sim</option><option value="0" @selected(!$s['ativo'])>Não</option></select>
                                    </label>
                                    @if(!empty($usuarios))
                                        @include('buriti-ui::select-multi', [
                                            'campo' => 'responsavel_ids[]',
                                            'rotulo' => 'Responsáveis',
                                            'icone' => 'fa-users',
                                            'opcoes' => collect($usuarios)->map(fn ($u) => [
                                                'id' => $u['id'],
                                                'nome' => $u['nome'],
                                                'meta' => $u['meta'] ?? ($u['login'] ?? ''),
                                            ])->all(),
                                            'selecionados' => collect($s['responsaveis'] ?? [])->pluck('user_id')->all(),
                                            'placeholder' => 'Digite o nome…',
                                            'ajuda' => 'Mesma lista filtrada por permissão do módulo.',
                                            'tamanho' => 5,
                                        ])
                                    @endif
                                    <label class="col-wide">Extras <input name="responsaveis_texto" placeholder="Nomes extras"></label>
                                    <div class="acoes"><button class="botao claro" type="submit">Salvar</button></div>
                                </form>
                            </details>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
