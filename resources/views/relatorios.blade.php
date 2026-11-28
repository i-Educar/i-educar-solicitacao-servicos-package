@extends('solicitacao::layout')
@section('titulo', 'Relatórios')
@section('migalha', 'Relatórios')
@section('icone-tela', 'fa-bar-chart')
@section('titulo-tela', 'Andamento dos chamados')
@section('lead')
    Situação, setor, prioridade e média de feedback · fonte {{ $fonte }}
@endsection

@section('acoes-topo')
    @include('exportacao::partials.botoes-exportar', [
        'action' => url('/solicitacao-servicos/relatorios/exportar'),
        'formatos' => ['pdf', 'xlsx', 'csv', 'html'],
        'opcoes' => [
            ['value' => 'situacao', 'label' => 'Por situação'],
            ['value' => 'prioridade', 'label' => 'Por prioridade'],
            ['value' => 'categoria', 'label' => 'Por categoria'],
            ['value' => 'completo', 'label' => 'Relatório completo'],
        ],
        'campoExtra' => [
            'data_inicio' => $dataInicio ?? '',
            'data_fim' => $dataFim ?? '',
        ],
        'tituloModal' => 'Download — Chamados',
    ])
    <button type="button" onclick="window.print()" class="botao claro icone" title="Imprimir" aria-label="Imprimir"><i aria-hidden="true" class="fa fa-print"></i></button>
@endsection

@section('conteudo')
    {{-- 5.6.5: Filtros de período --}}
    <div class="bloco">
        <form method="GET" action="/solicitacao-servicos/relatorios" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div>
                <label for="data_inicio">Data início</label>
                <input type="date" name="data_inicio" id="data_inicio" value="{{ $dataInicio ?? '' }}" style="padding: 0.5rem;">
            </div>
            <div>
                <label for="data_fim">Data fim</label>
                <input type="date" name="data_fim" id="data_fim" value="{{ $dataFim ?? '' }}" style="padding: 0.5rem;">
            </div>
            <div>
                <button type="submit" class="botao" style="background: var(--cor); color: white; padding: 0.5rem 1rem; border: none; cursor: pointer;">
                    <i class="fa fa-filter"></i> Filtrar
                </button>
            </div>
            @if($dataInicio !== '' || $dataFim !== '')
                <div>
                    <a href="/solicitacao-servicos/relatorios" class="botao" style="padding: 0.5rem 1rem;">
                        <i class="fa fa-times"></i> Limpar
                    </a>
                </div>
            @endif
        </form>
    </div>

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

    {{-- 5.6.5: Lista detalhada de chamados do período --}}
    @if(!empty($chamados))
    <div class="bloco">
        <h2>Chamados do período ({{ count($chamados) }})</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Escola</th>
                        <th>Assunto</th>
                        <th>Categoria</th>
                        <th>Prioridade</th>
                        <th>Situação</th>
                        <th>Setor</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($chamados as $c)
                    <tr>
                        <td><a href="/solicitacao-servicos/chamado/{{ $c['id'] }}" style="color: var(--cor); text-decoration: none;">#{{ $c['id'] }}</a></td>
                        <td>{{ !empty($c['aberto_em']) ? \Carbon\Carbon::parse($c['aberto_em'])->format('d/m/Y H:i') : '—' }}</td>
                        <td>{{ $c['escola'] ?? '—' }}</td>
                        <td>{{ $c['assunto'] ?? '—' }}</td>
                        <td>{{ $c['categoria'] ?? '—' }}</td>
                        <td>{{ ucfirst($c['prioridade'] ?? 'média') }}</td>
                        <td>
                            <span style="padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; background: {{ $c['situacao'] === 'fechado' ? 'var(--ok)' : ($c['situacao'] === 'aberto' ? 'var(--ambar)' : 'var(--destaque)') }}; color: white;">
                                {{ ucfirst($c['situacao'] ?? 'aberto') }}
                            </span>
                        </td>
                        <td>{{ $c['setor'] ?? 'sem setor' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <style media="print">
        form, .botao, .numeros { display: none !important; }
        body { font-size: 12px; }
    </style>
@endsection
