@extends('solicitacao::layout')
@php
    $setor = $setor ?? ['id' => 0, 'nome' => '', 'descricao' => '', 'ativo' => true, 'responsaveis' => []];
    $resp = $setor['responsaveis'] ?? [];
    $idsSelecionados = collect($resp)->pluck('user_id')->filter(fn ($id) => (int) $id > 0)->values()->all();
    $extrasTexto = collect($resp)->filter(fn ($r) => (int) ($r['user_id'] ?? 0) === 0)->pluck('nome')->implode(', ');
@endphp
@section('titulo', 'Editar setor · '.$setor['nome'])
@section('migalha', 'Editar setor')
@section('icone-tela', 'fa-pencil')
@section('titulo-tela', 'Editar setor')
@section('lead')
    Altere dados do setor e aloque usuários que já têm permissão de atender chamados.
@endsection
@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/solicitacao-servicos/setores') }}" title="Voltar à lista" aria-label="Voltar à lista"><i aria-hidden="true" class="fa fa-arrow-left"></i></a>
@endsection

@section('conteudo')
    <div class="setor-edit-layout">
        <section class="fluxo-bloco acao">
            <header class="fluxo-cabeca">
                <div>
                    <h2><i class="fa fa-building"></i> {{ $setor['nome'] }}</h2>
                    <p>Identificação e situação do setor no fluxo de chamados.</p>
                </div>
                <span class="selo-fluxo">Edição</span>
            </header>

            @if(empty($podeGravar))
                <p class="flash warn" style="margin:0 0 12px">Somente leitura — seu tipo de usuário não tem permissão de gravar setores.</p>
                <dl class="setor-edit-resumo">
                    <div><dt>Nome</dt><dd>{{ $setor['nome'] }}</dd></div>
                    <div><dt>Descrição</dt><dd>{{ $setor['descricao'] !== '' ? $setor['descricao'] : '—' }}</dd></div>
                    <div><dt>SLA</dt><dd>{{ (int) ($setor['sla_horas'] ?? 72) }} horas</dd></div>
                    <div><dt>Situação</dt><dd>{{ !empty($setor['ativo']) ? 'Ativo' : 'Inativo' }}</dd></div>
                </dl>
                <div class="setor-edit-alocados">
                    <h3><i class="fa fa-users"></i> Responsáveis alocados</h3>
                    @if(empty($resp))
                        <p class="meta">Nenhum responsável.</p>
                    @else
                        <ul class="setor-chips">
                            @foreach($resp as $r)
                                <li class="{{ (int) ($r['user_id'] ?? 0) > 0 ? 'sistema' : 'livre' }}">
                                    <i class="fa {{ (int) ($r['user_id'] ?? 0) > 0 ? 'fa-user' : 'fa-tag' }}"></i>
                                    {{ $r['nome'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @else
                <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/setores/'.$setor['id']) }}">
                    @csrf
                    <label>Nome <input name="nome" value="{{ $setor['nome'] }}" required></label>
                    <label>SLA (horas)
                        <input type="number" name="sla_horas" min="1" max="8760"
                               value="{{ (int) ($setor['sla_horas'] ?? 72) }}" required>
                    </label>
                    <label>Situação
                        <select name="ativo">
                            <option value="1" @selected(!empty($setor['ativo']))>Ativo</option>
                            <option value="0" @selected(empty($setor['ativo']))>Inativo</option>
                        </select>
                    </label>
                    <label class="col-wide">Descrição
                        <input name="descricao" value="{{ $setor['descricao'] }}" placeholder="Escopo do setor">
                    </label>

                    <div class="col-wide setor-edit-alocacao">
                        <header>
                            <h3><i class="fa fa-user-plus"></i> Alocação de usuários</h3>
                            <p>Marque quem responde e encaminha chamados deste setor. A lista só inclui pessoas com permissão no módulo (setores, responder ou encaminhar) e no seu escopo.</p>
                        </header>

                        @if(!empty($usuarios))
                            @include('buriti-ui::select-multi', [
                                'campo' => 'responsavel_ids[]',
                                'rotulo' => 'Usuários com permissão',
                                'icone' => 'fa-users',
                                'opcoes' => collect($usuarios)->map(fn ($u) => [
                                    'id' => $u['id'],
                                    'nome' => $u['nome'],
                                    'meta' => $u['meta'] ?? ($u['login'] ?? ''),
                                ])->all(),
                                'selecionados' => $idsSelecionados,
                                'placeholder' => 'Digite nome, login ou matrícula…',
                                'ajuda' => 'Ctrl/Cmd para marcar vários. Quem já estava alocado e perdeu permissão continua listado para você revisar.',
                                'tamanho' => 10,
                            ])
                        @else
                            <p class="flash warn" style="margin:0">Nenhuma pessoa elegível — configure o menu do tipo de usuário (visualiza em Setores / Responder / Encaminhar).</p>
                        @endif

                        <label class="col-wide" style="margin-top:8px">
                            Nomes extras (texto livre)
                            <input name="responsaveis_texto" value="{{ $extrasTexto }}" placeholder="Só se a pessoa ainda não estiver no sistema">
                        </label>
                    </div>

                    <div class="acoes">
                        <a class="botao claro" href="{{ url('/solicitacao-servicos/setores') }}">Cancelar</a>
                        <button class="botao" type="submit"><i class="fa fa-save"></i> Salvar setor</button>
                    </div>
                </form>
            @endif
        </section>

        <aside class="fluxo-bloco consulta setor-edit-ajuda" aria-label="Sobre permissões">
            <header class="fluxo-cabeca">
                <div>
                    <h2><i class="fa fa-shield"></i> Permissões</h2>
                    <p>Quem pode aparecer nesta alocação.</p>
                </div>
                <span class="selo-fluxo">Regras</span>
            </header>
            <ul class="setor-edit-regras">
                <li><i class="fa fa-clock-o"></i> O <strong>SLA do setor</strong> vale para chamados abertos ou encaminhados a ele</li>
                <li><i class="fa fa-check"></i> Tipo de usuário com <strong>visualiza</strong> em Setores, Responder ou Encaminhar</li>
                <li><i class="fa fa-check"></i> Escopo da escola / SME do usuário logado</li>
                <li><i class="fa fa-check"></i> Conta ativa no i-Educar</li>
                <li><i class="fa fa-info-circle"></i> Nomes em texto livre não entram no login — use só como referência</li>
            </ul>
            <p class="meta">
                SLA atual: <strong>{{ (int) ($setor['sla_horas'] ?? 72) }}h</strong>
                · Alocados: <strong>{{ count($resp) }}</strong>
                · Com usuário: <strong>{{ count($idsSelecionados) }}</strong>
            </p>
        </aside>
    </div>

<style>
.setor-edit-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(240px, 300px);
    gap: 14px;
    align-items: start;
}
.setor-edit-resumo {
    margin: 0 0 14px; display: grid; gap: 10px;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}
.setor-edit-resumo dt { font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--suave); }
.setor-edit-resumo dd { margin: 2px 0 0; font-weight: 700; }
.setor-edit-alocacao {
    padding: 12px;
    border-radius: 14px;
    border: 1px solid color-mix(in srgb, #0e7490 22%, var(--linha));
    background: linear-gradient(160deg, color-mix(in srgb, #e6f7f5 70%, #fff), #fff 55%);
}
.setor-edit-alocacao > header { margin: 0 0 10px; }
.setor-edit-alocacao h3 { margin: 0 0 4px; font-size: .98rem; display: flex; align-items: center; gap: 8px; }
.setor-edit-alocacao > header p { margin: 0; color: var(--suave); font-size: .86rem; line-height: 1.4; }
.setor-edit-ajuda .setor-edit-regras {
    list-style: none; margin: 0; padding: 0; display: grid; gap: 10px;
}
.setor-edit-ajuda .setor-edit-regras li {
    display: grid; grid-template-columns: 18px 1fr; gap: 8px; align-items: start;
    font-size: .88rem; line-height: 1.4; color: var(--tinta);
}
.setor-edit-ajuda .setor-edit-regras i { color: #0e7490; margin-top: 2px; }
.setor-edit-ajuda .meta { margin: 14px 0 0; color: var(--suave); font-size: .85rem; }
.setor-chips { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 6px; }
.setor-chips li {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 9px; border-radius: 999px; font-size: .78rem; font-weight: 650;
    border: 1px solid var(--linha); background: #eef1f3;
}
.setor-chips li.sistema { border-color: color-mix(in srgb, #0e7490 28%, var(--linha)); background: #e6f7f5; color: #0e7490; }
.setor-chips li.livre { border-color: color-mix(in srgb, var(--ambar) 28%, var(--linha)); background: #fff6e8; color: var(--ambar); }
@media (max-width: 900px) {
    .setor-edit-layout { grid-template-columns: 1fr; }
}
</style>
@endsection
