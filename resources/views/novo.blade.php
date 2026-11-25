@extends('solicitacao::layout')
@section('titulo', 'Abrir pedido de serviço')
@section('migalha', 'Novo pedido')
@section('icone-tela', 'fa-plus')
@section('titulo-tela', 'Abrir pedido de serviço')
@section('lead')
    Descreva o que a escola precisa, escolha o setor e, se quiser, anexe um PDF.
@endsection
@section('acoes-topo')
    <a class="botao claro" href="{{ url('/solicitacao-servicos') }}"><i class="fa fa-dashboard"></i> Início</a>
    <a class="botao claro" href="{{ url('/solicitacao-servicos/abertos') }}"><i class="fa fa-list"></i> Em andamento</a>
@endsection

@section('conteudo')
@php
    $rotuloPrioridade = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
    $rotuloCategoria = [
        'manutencao' => 'Manutenção',
        'ti' => 'TI / Informática',
        'geral' => 'Geral',
    ];
@endphp

    <div class="bloco">
        @if(!empty($ativo))
            <form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/novo') }}" enctype="multipart/form-data">
                @csrf
                @include('buriti-ui::select-busca', [
                    'campo' => 'escola',
                    'rotulo' => 'Escola',
                    'icone' => 'fa-building',
                    'opcoes' => collect($escolas ?? [])->map(fn ($e) => [
                        'id' => $e['nome'],
                        'nome' => $e['nome'],
                        'meta' => ! empty($e['cod']) ? 'código '.$e['cod'] : '',
                    ])->all(),
                    'selecionado' => old('escola'),
                    'placeholder' => 'Digite o nome da escola…',
                    'obrigatorio' => true,
                    'ajuda' => 'Escolha a escola que está pedindo o serviço.',
                ])
                @include('buriti-ui::select-busca', [
                    'campo' => 'prioridade',
                    'rotulo' => 'Urgência',
                    'icone' => 'fa-bolt',
                    'opcoes' => collect($prioridades ?? ['alta', 'media', 'baixa'])->map(fn ($p) => [
                        'id' => $p,
                        'nome' => $rotuloPrioridade[$p] ?? ucfirst($p),
                        'meta' => 'prazo '.($slaHoras[$p] ?? '—').'h',
                    ])->all(),
                    'selecionado' => old('prioridade', 'media'),
                    'placeholder' => 'Alta, média ou baixa…',
                    'obrigatorio' => true,
                    'vazio' => '— escolher urgência —',
                    'tamanho' => 4,
                    'ajuda' => 'Alta = mais urgente. O prazo (SLA) muda conforme a urgência.',
                ])
                @include('buriti-ui::select-busca', [
                    'campo' => 'categoria',
                    'rotulo' => 'Tipo de pedido',
                    'icone' => 'fa-tags',
                    'opcoes' => collect($categorias ?? [['nome' => 'geral']])->map(fn ($c) => [
                        'id' => $c['nome'],
                        'nome' => $rotuloCategoria[$c['nome']] ?? ucfirst(str_replace('_', ' ', (string) $c['nome'])),
                        'meta' => ! empty($c['sla_horas']) ? 'prazo '.$c['sla_horas'].'h' : '',
                    ])->all(),
                    'selecionado' => old('categoria'),
                    'placeholder' => 'Buscar tipo…',
                    'obrigatorio' => true,
                    'ajuda' => 'Ex.: manutenção, limpeza, informática. Cadastre tipos em «Tipos de pedido».',
                ])
                @include('buriti-ui::select-busca', [
                    'campo' => 'setor_id',
                    'rotulo' => 'Setor que vai atender',
                    'icone' => 'fa-sitemap',
                    'opcoes' => collect($setores ?? [])
                        ->filter(fn ($s) => ($s['id'] ?? 0) > 0 && ! empty($s['ativo'] ?? true))
                        ->map(fn ($s) => [
                            'id' => $s['id'],
                            'nome' => $s['nome'],
                            'meta' => count($s['responsaveis'] ?? []).' responsável(is)',
                        ])->values()->all(),
                    'selecionado' => old('setor_id', 0),
                    'placeholder' => 'Buscar setor…',
                    'vazio' => '— triagem da SME (sem setor ainda) —',
                    'ajuda' => 'Se não souber o setor, deixe em triagem. Depois a equipe encaminha.',
                ])
                <label class="col-wide">Assunto
                    <input name="assunto" required maxlength="180" value="{{ old('assunto') }}" placeholder="Ex.: Vazamento no banheiro feminino">
                </label>
                <label class="col-wide">Descrição
                    <textarea name="descricao" placeholder="Detalhes para a equipe de serviços…">{{ old('descricao') }}</textarea>
                </label>
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'anexo',
                    'rotulo' => 'Anexar PDF (opcional)',
                    'tituloAcao' => 'Soltar o PDF aqui',
                    'ajuda' => 'Foto do problema? Escaneie em PDF e envie — máx. '.($anexoMaxKb ?? 5120).' KB.',
                    'accept' => 'application/pdf,.pdf',
                    'icone' => 'fa-file-pdf-o',
                ])
                <div class="acoes">
                    <button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Enviar pedido</button>
                </div>
            </form>
        @else
            <div class="buriti-alerta aviso">
                <i class="fa fa-info-circle"></i>
                <div>
                    <strong>Abertura desligada</strong>
                    <p>A abertura de novos pedidos está desabilitada na configuração do módulo.</p>
                </div>
            </div>
        @endif
    </div>
@endsection
