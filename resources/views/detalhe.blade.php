@extends('solicitacao::layout')
@php
    $status = $chamado['situacao'] ?? 'aberto';
    $rotulo = $rotulosStatus[$status] ?? ucfirst($status);
    $prio = (string) ($chamado['prioridade'] ?? 'media');
    $prioRotulo = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'][$prio] ?? $prio;
    $euNome = auth()->user()?->name ?? auth()->user()?->login ?? '';
@endphp
@section('titulo', 'Pedido #'.$chamado['id'])
@section('migalha', 'Pedido #'.$chamado['id'])
@section('icone-tela', 'fa-comments')
@section('titulo-tela', 'Conversa do pedido #'.$chamado['id'])
@section('lead')
    {{ $chamado['assunto'] }} · {{ $chamado['escola'] }}
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/solicitacao-servicos/abertos') }}"><i class="fa fa-folder-open"></i> Pedidos</a>
    @if(!empty($podeEncaminhar))
        <form method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/assumir') }}" style="display:inline">@csrf
            <button class="botao" type="submit"><i class="fa fa-hand-paper-o"></i> Assumir atendimento</button>
        </form>
    @endif
@endsection

@section('conteudo')
    <div class="bloco">
        <p>
            <span class="badge {{ \iEducar\Packages\BuritiSolicitacaoServicos\Services\FluxoChamado::tom($status) }}">{{ $rotulo }}</span>
            <span class="badge warn">Urgência {{ $prioRotulo }}</span>
            @if(!empty($chamado['setor']))
                <span class="badge warn"><i class="fa fa-sitemap"></i> {{ $chamado['setor'] }}</span>
            @endif
            @if(!empty($chamado['responsavel']))
                <span class="badge ok"><i class="fa fa-user"></i> {{ $chamado['responsavel'] }}</span>
            @endif
        </p>
        <dl class="form-grid" style="margin-top:12px">
            <div><strong>Aberto em</strong><br>{{ $chamado['aberto_em'] }}</div>
            <div><strong>Quem pediu</strong><br>{{ $chamado['solicitante'] ?? '—' }}</div>
            <div><strong>Tipo</strong><br>{{ $chamado['categoria'] ?? 'Geral' }}</div>
            <div><strong>Escola</strong><br>{{ $chamado['escola'] }}</div>
            @if(!empty($chamado['descricao']))
                <div class="col-wide"><strong>Detalhes do pedido</strong><br>{{ $chamado['descricao'] }}</div>
            @endif
            @if(!empty($chamado['parecer']))
                <div class="col-wide"><strong>Como ficou</strong><br>{{ $chamado['parecer'] }}</div>
            @endif
            @if(!empty($chamado['feedback']))
                <div class="col-wide"><strong>Avaliação de quem pediu</strong><br>
                    {{ str_repeat('★', (int)($chamado['feedback']['nota'] ?? 0)) }}{{ str_repeat('☆', 5 - (int)($chamado['feedback']['nota'] ?? 0)) }}
                    @if(!empty($chamado['feedback']['comentario'])) — {{ $chamado['feedback']['comentario'] }}@endif
                </div>
            @endif
        </dl>
        @if(!empty($anexos))
            <p style="margin:14px 0 6px;font-weight:800"><i class="fa fa-paperclip"></i> Papéis anexados</p>
            <ul class="buriti-anexo-lista">
                @foreach($anexos as $a)
                    <li>
                        <a href="{{ url('/solicitacao-servicos/anexo/'.$a['id']) }}">
                            <i class="fa fa-file-pdf-o"></i>
                            {{ $a['nome'] }}
                            <small style="font-weight:500;color:var(--suave)">({{ number_format(($a['tamanho'] ?? 0)/1024, 0) }} KB)</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="bloco" style="padding:0;overflow:hidden;border-top-width:0">
        <div class="buriti-chat">
            <div class="buriti-chat-topo">
                <span class="avatar"><i class="fa fa-comments"></i></span>
                <div>
                    <strong>{{ $chamado['assunto'] }}</strong>
                    <small>{{ $chamado['escola'] }} · como no WhatsApp — mensagens e recados da equipe</small>
                </div>
            </div>
            <div class="buriti-chat-fios" id="chat-fios">
                @if(empty($timeline))
                    <div class="buriti-chat-vazio">
                        Ainda não há mensagens.<br>Escreva abaixo como faria no WhatsApp.
                    </div>
                @else
                    @foreach($timeline as $item)
                        @php
                            $ehMsg = ($item['kind'] ?? '') === 'mensagem';
                            $autor = (string) ($item['autor'] ?? '');
                            $lado = ! $ehMsg && empty($item['texto'])
                                ? 'sistema'
                                : (($euNome !== '' && mb_stripos($autor, $euNome) !== false) ? 'eu' : ($ehMsg ? 'eles' : 'sistema'));
                            if (! $ehMsg && ! empty($item['texto'])) {
                                $lado = ($euNome !== '' && mb_stripos($autor, $euNome) !== false) ? 'eu' : 'eles';
                            }
                            $selo = $ehMsg ? 'Mensagem' : 'Recado interno';
                            if (! empty($item['para_status'])) {
                                $selo = 'Atualização';
                                $lado = 'sistema';
                            }
                        @endphp
                        <div class="buriti-balao {{ $lado }}">
                            @if($lado !== 'sistema')
                                <span class="selo">{{ $selo }}</span>
                                <span class="quem">{{ $autor !== '' ? $autor : 'Equipe' }}</span>
                            @endif
                            <p class="texto">{{ $item['texto'] }}</p>
                            @if(!empty($item['para_status']))
                                <p class="texto" style="margin-top:4px">
                                    @if(!empty($item['de_status'])){{ $rotulosStatus[$item['de_status']] ?? $item['de_status'] }} → @endif
                                    {{ $rotulosStatus[$item['para_status']] ?? $item['para_status'] }}
                                    @if(!empty($item['setor'])) · {{ $item['setor'] }}@endif
                                    @if(!empty($item['responsavel'])) · {{ $item['responsavel'] }}@endif
                                </p>
                            @endif
                            @foreach(($item['anexo_ids'] ?? []) as $aid)
                                @if(!empty($anexosPorId[$aid]))
                                    <a class="anexo-chip" href="{{ url('/solicitacao-servicos/anexo/'.$aid) }}">
                                        <i class="fa fa-paperclip"></i> {{ $anexosPorId[$aid]['nome'] }}
                                    </a>
                                @endif
                            @endforeach
                            <span class="quando">{{ $item['em'] ?: ($item['created_at'] ?? '') }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            @if(!empty($podeResponder))
                <div class="buriti-chat-abas" role="tablist">
                    <button type="button" class="ativo" data-chat-aba="resposta">Mensagem para a escola</button>
                    <button type="button" data-chat-aba="andamento">Recado só da equipe</button>
                </div>
                <form class="buriti-chat-composer" id="form-chat-resposta" method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/responder') }}" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <textarea name="texto" required placeholder="Digite a mensagem…" rows="2"></textarea>
                        @include('buriti-ui::campo-anexo', [
                            'campo' => 'anexo',
                            'rotulo' => 'Anexar PDF (opcional)',
                            'tituloAcao' => 'Soltar o PDF aqui',
                            'ajuda' => 'Como enviar um arquivo no WhatsApp — toque ou arraste. Máx. '.($anexoMaxKb ?? 5120).' KB.',
                            'accept' => 'application/pdf,.pdf',
                            'icone' => 'fa-file-pdf-o',
                        ])
                    </div>
                    <div class="composer-acoes">
                        <button class="botao-enviar" type="submit" title="Enviar"><i class="fa fa-paper-plane"></i></button>
                    </div>
                </form>
                <form class="buriti-chat-composer" id="form-chat-andamento" method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/andamento') }}" style="display:none">
                    @csrf
                    <div>
                        <textarea name="texto" required placeholder="Recado interno: visita, telefonema, fornecedor…" rows="2"></textarea>
                        <small style="display:block;margin:6px 2px 0;color:#667781;font-size:.75rem">Só a equipe vê este recado — a escola não recebe.</small>
                    </div>
                    <div class="composer-acoes">
                        <button class="botao-enviar" type="submit" title="Salvar recado"><i class="fa fa-check"></i></button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if(!empty($podeEncaminhar))
        <div class="fluxo-sep">Encaminhamento</div>
        <section class="fluxo-bloco equipe">
            <header class="fluxo-cabeca">
                <div>
                    <h2><i class="fa fa-share"></i> Passar para outro setor</h2>
                    <p>Como transferir um atendimento: primeiro o setor; a pessoa responsável vem da lista daquele setor.</p>
                </div>
                <span class="selo-fluxo">Encaminhar</span>
            </header>
            <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/encaminhar') }}" id="form-encaminhar">
                @csrf
                @include('buriti-ui::select-busca', [
                    'campo' => 'setor_id',
                    'id' => 'enc-setor',
                    'rotulo' => 'Setor de destino',
                    'icone' => 'fa-sitemap',
                    'opcoes' => collect($setores ?? [])
                        ->filter(fn ($s) => ($s['id'] ?? 0) > 0)
                        ->map(fn ($s) => [
                            'id' => $s['id'],
                            'nome' => $s['nome'],
                            'meta' => count($s['responsaveis'] ?? []).' responsável(is)',
                            'data' => ['resp' => $s['responsaveis'] ?? []],
                        ])->values()->all(),
                    'selecionado' => $chamado['setor_id'] ?? null,
                    'placeholder' => 'Buscar setor…',
                    'obrigatorio' => true,
                    'vazio' => '— escolher setor —',
                    'onchange' => "window.__solEncSetor && window.__solEncSetor(this)",
                    'ajuda' => 'Obrigatório. Os responsáveis abaixo mudam conforme o setor.',
                ])
                <label class="buriti-select col-wide">
                    <span class="buriti-select-rotulo"><i class="fa fa-user"></i> Responsável do setor (opcional)</span>
                    <select name="responsavel_id" id="enc-resp" class="buriti-select-lista" size="5">
                        <option value="0">— opcional —</option>
                    </select>
                    <small class="buriti-select-ajuda">Só as pessoas já ligadas a este setor — não a rede inteira.</small>
                </label>
                <label>Ou digite o nome <input name="responsavel" placeholder="Se a pessoa não estiver no setor"></label>
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-share"></i> Encaminhar</button></div>
            </form>
        </section>
        <script>
        (function () {
            window.__solEncSetor = function (sel) {
                var r = document.getElementById('enc-resp');
                if (!r) return;
                r.innerHTML = '<option value="0">— opcional —</option>';
                var o = sel.options[sel.selectedIndex];
                if (!o || !o.value) return;
                try {
                    var data = JSON.parse(o.getAttribute('data-resp') || '[]');
                    (data || []).forEach(function (x) {
                        var op = document.createElement('option');
                        op.value = x.user_id;
                        op.textContent = x.nome || ('#' + x.user_id);
                        r.appendChild(op);
                    });
                } catch (e) {}
            };
            var sel = document.getElementById('enc-setor');
            if (sel && sel.value) window.__solEncSetor(sel);
        })();
        </script>
    @endif

    @if(!empty($proximos))
        <div class="fluxo-sep">Situação do chamado</div>
        <div class="bloco">
            <h2><i class="fa fa-flag"></i> Mudar a situação do pedido</h2>
            <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/status') }}">
                @csrf
                <label>Próxima situação
                    <select name="status" required>
                        @foreach($proximos as $st)
                            <option value="{{ $st }}">{{ $rotulosStatus[$st] ?? $st }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="col-wide">Observação (opcional) <input name="nota" placeholder="Motivo da mudança"></label>
                <div class="acoes"><button class="botao claro" type="submit"><i class="fa fa-check"></i> Atualizar</button></div>
            </form>
        </div>
    @endif

    @if(!empty($podeDeferir) || !empty($podeIndeferir))
        <div class="bloco">
            <h2><i class="fa fa-handshake-o"></i> Encerrar o pedido</h2>
            <div class="form-grid">
                @if(!empty($podeDeferir))
                    <form method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/deferir') }}">
                        @csrf
                        <label class="col-wide">Marcar como resolvido
                            <textarea name="parecer" placeholder="O que foi feito">Resolvido — atendimento concluído.</textarea>
                        </label>
                        <div class="acoes"><button class="botao" type="submit"><i class="fa fa-check"></i> Resolver</button></div>
                    </form>
                @endif
                @if(!empty($podeIndeferir))
                    <form method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/indeferir') }}">
                        @csrf
                        <label class="col-wide">Não atender / indeferir
                            <textarea name="parecer" placeholder="Motivo">Indeferido — fora do escopo.</textarea>
                        </label>
                        <div class="acoes"><button class="botao claro" type="submit"><i class="fa fa-times"></i> Indeferir</button></div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if(!empty($podeFeedback))
        <div class="bloco">
            <h2><i class="fa fa-star"></i> Como foi o atendimento?</h2>
            <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/feedback') }}">
                @csrf
                <label>Nota
                    <select name="nota" required>
                        @for($i=5;$i>=1;$i--)
                            <option value="{{ $i }}">{{ $i }} — {{ ['','Péssimo','Ruim','Regular','Bom','Ótimo'][$i] }}</option>
                        @endfor
                    </select>
                </label>
                <label class="col-wide">Comentário <input name="comentario" placeholder="Opcional"></label>
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-star"></i> Enviar avaliação</button></div>
            </form>
        </div>
    @endif

    <script>
    (function () {
        var fios = document.getElementById('chat-fios');
        if (fios) fios.scrollTop = fios.scrollHeight;
        var abas = document.querySelectorAll('[data-chat-aba]');
        var formResp = document.getElementById('form-chat-resposta');
        var formAnd = document.getElementById('form-chat-andamento');
        abas.forEach(function (btn) {
            btn.addEventListener('click', function () {
                abas.forEach(function (b) { b.classList.remove('ativo'); });
                btn.classList.add('ativo');
                var qual = btn.getAttribute('data-chat-aba');
                if (formResp) formResp.style.display = qual === 'resposta' ? '' : 'none';
                if (formAnd) formAnd.style.display = qual === 'andamento' ? '' : 'none';
            });
        });
    })();
    </script>
@endsection
