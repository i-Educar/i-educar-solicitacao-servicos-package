@extends('solicitacao::layout')
@section('titulo', 'Setores e responsáveis')
@section('migalha', 'Setores')
@section('icone-tela', 'fa-sitemap')
@section('titulo-tela', 'Setores e responsáveis')
@section('lead')
    Organize para onde os chamados vão e quem atende — a lista de pessoas já respeita permissão do módulo e o seu escopo.
@endsection
@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/solicitacao-servicos/novo') }}" title="Abrir chamado" aria-label="Abrir chamado"><i aria-hidden="true" class="fa fa-plus"></i></a>
@endsection

@section('conteudo')
@php
    $setores = $setores ?? [];
    $ativos = collect($setores)->where('ativo', true)->count();
    $semResp = collect($setores)->filter(fn ($s) => empty($s['responsaveis']))->count();
@endphp

@if(!empty($podeGravar))
    <div class="fluxo-sep">Cadastrar</div>
    <section class="fluxo-bloco acao">
        <header class="fluxo-cabeca">
            <div>
                <h2><i class="fa fa-plus"></i> Novo setor</h2>
                <p>Crie o departamento e indique quem responde os chamados.</p>
            </div>
            <span class="selo-fluxo">Novo</span>
        </header>
        <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/setores') }}">
            @csrf
            <label>Nome <input name="nome" required placeholder="Ex.: Manutenção predial"></label>
            <label>SLA (horas)
                <input type="number" name="sla_horas" min="1" max="8760" value="72" required>
            </label>
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

    <div class="setor-resumo numeros" aria-label="Resumo dos setores">
        <article style="--tom:#0e7490">
            <div class="kpi-ico"><i class="fa fa-sitemap"></i></div>
            <span>Cadastrados</span>
            <strong>{{ count($setores) }}</strong>
        </article>
        <article style="--tom:var(--ok)">
            <div class="kpi-ico"><i class="fa fa-check-circle"></i></div>
            <span>Ativos</span>
            <strong>{{ $ativos }}</strong>
        </article>
        <article style="--tom:var(--ambar)">
            <div class="kpi-ico"><i class="fa fa-user-times"></i></div>
            <span>Sem responsável</span>
            <strong>{{ $semResp }}</strong>
        </article>
    </div>

    <section class="fluxo-bloco consulta setor-lista-bloco">
        <header class="fluxo-cabeca">
            <div>
                <h2><i class="fa fa-building"></i> Lista de setores</h2>
                <p>Consulte e abra a edição para alocar usuários com permissão no módulo.</p>
            </div>
            <span class="selo-fluxo">Lista</span>
        </header>

        @if(empty($setores))
            <div class="setor-vazio">
                <i class="fa fa-sitemap" aria-hidden="true"></i>
                <p>Nenhum setor cadastrado.</p>
                @if(!empty($podeGravar))
                    <p class="meta">Use o formulário acima para criar o primeiro.</p>
                @endif
            </div>
        @else
            <div class="setor-grade">
                @foreach($setores as $i => $s)
                    @php
                        $resp = $s['responsaveis'] ?? [];
                        $qtd = count($resp);
                        $extras = collect($resp)->filter(fn ($r) => (int) ($r['user_id'] ?? 0) === 0)->count();
                    @endphp
                    <article class="setor-card {{ empty($s['ativo']) ? 'inativo' : '' }}" style="animation-delay: {{ $i * 0.04 }}s">
                        <header class="setor-card-topo">
                            <span class="setor-card-ico" aria-hidden="true"><i class="fa fa-building"></i></span>
                            <div class="setor-card-titulo">
                                <h3>{{ $s['nome'] }}</h3>
                                <p>{{ $s['descricao'] !== '' ? $s['descricao'] : 'Sem descrição' }}</p>
                            </div>
                            <span class="badge {{ !empty($s['ativo']) ? 'ok' : 'fail' }}">
                                {{ !empty($s['ativo']) ? 'Ativo' : 'Inativo' }}
                            </span>
                        </header>

                        <div class="setor-card-corpo">
                            <div class="setor-card-meta">
                                <span><i class="fa fa-clock-o"></i> SLA {{ (int) ($s['sla_horas'] ?? 72) }}h</span>
                                <span><i class="fa fa-users"></i> {{ $qtd }} responsável{{ $qtd === 1 ? '' : 'es' }}</span>
                                @if($extras > 0)
                                    <span class="aviso"><i class="fa fa-pencil"></i> {{ $extras }} texto livre</span>
                                @endif
                            </div>
                            @if($qtd === 0)
                                <p class="setor-sem-resp"><i class="fa fa-exclamation-circle"></i> Sem responsáveis alocados</p>
                            @else
                                <ul class="setor-chips" aria-label="Responsáveis">
                                    @foreach(array_slice($resp, 0, 6) as $r)
                                        <li class="{{ (int) ($r['user_id'] ?? 0) > 0 ? 'sistema' : 'livre' }}" title="{{ (int) ($r['user_id'] ?? 0) > 0 ? 'Usuário do sistema' : 'Nome livre' }}">
                                            <i class="fa {{ (int) ($r['user_id'] ?? 0) > 0 ? 'fa-user' : 'fa-tag' }}"></i>
                                            {{ $r['nome'] }}
                                        </li>
                                    @endforeach
                                    @if($qtd > 6)
                                        <li class="mais">+{{ $qtd - 6 }}</li>
                                    @endif
                                </ul>
                            @endif
                        </div>

                        <footer class="setor-card-rodape">
                            @if(!empty($podeGravar) && ($s['id'] ?? 0) > 0)
                                <a class="botao" href="{{ url('/solicitacao-servicos/setores/'.$s['id'].'/editar') }}">
                                    <i class="fa fa-pencil"></i> Editar setor
                                </a>
                            @elseif(($s['id'] ?? 0) > 0)
                                <a class="botao claro" href="{{ url('/solicitacao-servicos/setores/'.$s['id'].'/editar') }}">
                                    <i class="fa fa-eye"></i> Ver detalhes
                                </a>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

<style>
.setor-resumo { margin-bottom: 8px; }
.setor-lista-bloco { margin-top: 4px; }
.setor-vazio {
    text-align: center; padding: 28px 16px; color: var(--suave);
    border: 1px dashed var(--linha); border-radius: 14px; background: color-mix(in srgb, #fff 70%, transparent);
}
.setor-vazio i { font-size: 1.6rem; opacity: .55; display: block; margin-bottom: 8px; }
.setor-vazio .meta { margin: 4px 0 0; font-size: .88rem; }
.setor-grade {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
}
.setor-card {
    display: flex; flex-direction: column; gap: 12px;
    background: #fff;
    border: 1px solid var(--linha);
    border-radius: 16px;
    padding: 14px 14px 12px;
    border-top: 3px solid #0e7490;
    box-shadow: 0 10px 22px rgba(21, 34, 43, .05);
    animation: sobe .4s ease both;
}
.setor-card.inativo {
    border-top-color: var(--suave);
    opacity: .88;
    background: color-mix(in srgb, #f4f7f9 55%, #fff);
}
.setor-card-topo { display: grid; grid-template-columns: 42px 1fr auto; gap: 10px; align-items: start; }
.setor-card-ico {
    width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; color: #fff;
    background: linear-gradient(145deg, #14b8a6, #0e7490);
    box-shadow: 0 8px 16px color-mix(in srgb, #0e7490 28%, transparent);
}
.setor-card.inativo .setor-card-ico {
    background: linear-gradient(145deg, #8aa0ad, #5b6b76);
    box-shadow: none;
}
.setor-card-titulo h3 { margin: 0; font-size: 1rem; line-height: 1.25; }
.setor-card-titulo p { margin: 3px 0 0; color: var(--suave); font-size: .82rem; line-height: 1.35; }
.setor-card-corpo { display: grid; gap: 8px; flex: 1; }
.setor-card-meta {
    display: flex; flex-wrap: wrap; gap: 8px 12px;
    font-size: .78rem; font-weight: 700; color: var(--suave);
}
.setor-card-meta .aviso { color: var(--ambar); }
.setor-sem-resp {
    margin: 0; padding: 8px 10px; border-radius: 10px; font-size: .85rem; font-weight: 600;
    color: var(--ambar); background: #fff6e8; border: 1px solid color-mix(in srgb, var(--ambar) 22%, #fff);
}
.setor-chips { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 6px; }
.setor-chips li {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 9px; border-radius: 999px; font-size: .78rem; font-weight: 650;
    border: 1px solid var(--linha); background: color-mix(in srgb, var(--clara) 55%, #fff); color: var(--tinta);
}
.setor-chips li.sistema { border-color: color-mix(in srgb, #0e7490 28%, var(--linha)); background: #e6f7f5; color: #0e7490; }
.setor-chips li.livre { border-color: color-mix(in srgb, var(--ambar) 28%, var(--linha)); background: #fff6e8; color: var(--ambar); }
.setor-chips li.mais { background: #eef1f3; color: var(--suave); }
.setor-card-rodape {
    display: flex; justify-content: flex-end; gap: 8px;
    padding-top: 4px; border-top: 1px solid color-mix(in srgb, var(--linha) 70%, transparent);
}
html[data-buriti-modo="escuro"] .setor-card {
    background: color-mix(in srgb, #1a242b 92%, #0e7490);
    border-color: color-mix(in srgb, #0e7490 25%, #334049);
}
html[data-buriti-modo="escuro"] .setor-chips li.sistema { background: color-mix(in srgb, #0e7490 22%, #1a242b); }
</style>
@endsection
