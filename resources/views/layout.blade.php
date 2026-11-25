<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Chamados')</title>
    <link rel="stylesheet" href="{{ url('/intranet/styles/font-awesome.css') }}">
    <style>
        :root {
            --cor: {{ $identidade['cor'] ?? '#b86e00' }};
            --destaque: {{ $identidade['destaque'] ?? '#e0a100' }};
            --clara: {{ $identidade['clara'] ?? '#fff6e8' }};
            --nav: #15222b; --nav-texto: #d5e0e6; --fundo: #e8edf0;
            --tinta: #1a242b; --suave: #5b6b76; --linha: #cfd8df;
            --ok: #17663f; --ok-fundo: #e5f6ec; --erro: #9b2c2c; --erro-fundo: #fdeceb;
            --ambar: #b86e00; --cor-tela: var(--cor);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; font-size: 14px; color: var(--tinta);
            background: radial-gradient(900px 360px at 8% -8%, color-mix(in srgb, var(--clara) 80%, transparent), transparent 55%), linear-gradient(180deg, #f3f6f8, var(--fundo)); }
        .app { display: flex; min-height: 100vh; }
        .lateral { width: 248px; flex: none; background: linear-gradient(180deg, color-mix(in srgb, var(--cor) 35%, #15222b), var(--nav)); color: var(--nav-texto); }
        .marca { display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; padding: 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .marca .marca-texto { display: none; }
        .marca .marca-titulo { display: block; font-size: .82rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; line-height: 1.15; }
        .marca .marca-desc { display: block; color: #8aa0ad; font-weight: 400; font-size: .72rem; line-height: 1.25; }
                .icone { width: 40px; height: 40px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(145deg, var(--destaque), var(--cor)); color: #fff; box-shadow: 0 8px 18px color-mix(in srgb, var(--cor) 35%, transparent); }
        .modulo { padding: 8px 10px; }
        .modulo-nome { width: 100%; display: flex; justify-content: space-between; margin: 0; padding: 8px; background: 0; border: 0; color: #8aa0ad; font: inherit; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; font-weight: 700; }
        .modulo a { display: flex; align-items: center; gap: 10px; color: var(--nav-texto); text-decoration: none; border-radius: 10px; padding: 8px 10px; }
        .modulo a i { width: 18px; text-align: center; }
        .modulo a:hover, .modulo a.ativo { background: color-mix(in srgb, var(--cor) 88%, #fff); color: #fff; font-weight: 700; }
        .miolo { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .topo { position: sticky; top: 0; z-index: 10; min-height: 56px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 18px; background: color-mix(in srgb,#fff 90%, transparent); backdrop-filter: blur(8px); border-bottom: 1px solid var(--linha); }
        .migalha { margin: 0; color: var(--suave); font-size: 13px; } .migalha a { color: var(--cor); font-weight: 700; text-decoration: none; }
        main { padding: 18px 20px 28px; flex: 1; }
        .cabeca-tela { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin: -18px -20px 16px; padding: 14px 16px 14px 20px;
            background: linear-gradient(120deg, color-mix(in srgb, var(--clara) 70%, #fff), #fff 55%); border-bottom: 1px solid var(--linha); border-left: 4px solid var(--cor-tela); }
        .identidade-tela { display: flex; align-items: center; gap: 12px; }
        .marca-tela { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(145deg, var(--destaque), var(--cor-tela)); color: #fff; font-size: 18px; box-shadow: 0 10px 20px color-mix(in srgb, var(--cor-tela) 28%, transparent); }
        .cabeca-tela h1 { margin: 0; font-size: 1.25rem; } .cabeca-tela .lead { margin: 2px 0 0; color: var(--suave); }
        .acoes-tela { display: flex; flex-wrap: wrap; gap: 8px; }
        .botao { display: inline-flex; align-items: center; gap: 8px; background: var(--cor-tela); color: #fff; border: 0; border-radius: 10px; padding: 8px 12px; font: inherit; text-decoration: none; cursor: pointer; }
        .botao.claro { background: #fff; color: var(--cor-tela); border: 1px solid color-mix(in srgb, var(--cor-tela) 35%, var(--linha)); }
        .flash { border-radius: 12px; padding: 12px 14px; margin: 0 0 16px; } .flash.ok { background: var(--ok-fundo); color: var(--ok); } .flash.erro { background: var(--erro-fundo); color: var(--erro); }
        .bloco { background: #fff; border: 1px solid var(--linha); border-radius: 14px; padding: 14px 16px; margin: 0 0 12px; border-top: 3px solid var(--cor-tela); box-shadow: 0 10px 24px rgba(21,34,43,.04); }
        .bloco h2 { margin: 0 0 10px; display: flex; align-items: center; gap: 8px; font-size: 1.05rem; }
        .numeros { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin: 0 0 16px; }
        .numeros article { --tom: var(--cor-tela); background: #fff; border: 1px solid color-mix(in srgb, var(--tom) 30%, var(--linha)); border-top: 3px solid var(--tom); border-radius: 14px; padding: 14px; box-shadow: 0 8px 18px rgba(21,34,43,.05); animation: sobe .4s ease both; }
        .numeros .kpi-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; margin: 0 0 8px; background: color-mix(in srgb, var(--tom) 12%, #fff); color: var(--tom); border: 1px solid color-mix(in srgb, var(--tom) 22%, #fff); }
        .numeros span { display: block; color: var(--suave); font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .numeros strong { font-size: 1.4rem; letter-spacing: -.03em; }
        .atalhos { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .atalhos a { display: grid; grid-template-columns: 42px 1fr; gap: 10px; align-items: center; min-height: 64px; padding: 10px 12px; border: 1px solid var(--linha); border-radius: 14px; text-decoration: none; color: var(--tinta); background: linear-gradient(180deg, color-mix(in srgb, var(--clara) 55%, #fff), #fff); font-weight: 700; }
        .atalhos a i { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; color: #fff; background: var(--destaque); }
        .atalhos a small { display: block; font-weight: 500; color: var(--suave); margin-top: 2px; }
        .atalhos a:hover { transform: translateY(-2px); border-color: var(--destaque); }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .card { background: #fff; border: 1px solid var(--linha); border-radius: 14px; padding: 14px; border-top: 3px solid var(--tom, var(--cor)); box-shadow: 0 8px 18px rgba(21,34,43,.04); animation: sobe .4s ease both; }
        .card header { display: flex; gap: 10px; align-items: center; margin: 0 0 8px; }
        .card .ico { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, var(--destaque), var(--tom, var(--cor))); }
        .card h3 { margin: 0; font-size: .95rem; } .card .meta { margin: 0; color: var(--suave); font-size: .78rem; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: .75rem; font-weight: 700; }
        .badge.ok { background: var(--ok-fundo); color: var(--ok); } .badge.fail { background: var(--erro-fundo); color: var(--erro); } .badge.warn { background: #fff6e8; color: var(--ambar); }
        table { width: 100%; border-collapse: collapse; } th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--linha); } th { background: var(--clara); color: var(--cor); }
        .grade { display: grid; grid-template-columns: 70px repeat(5, 1fr); gap: 4px; }
        .grade .h, .grade .c { padding: 8px; border-radius: 8px; background: #fff; border: 1px solid var(--linha); font-size: .78rem; text-align: center; }
        .grade .h { background: var(--clara); font-weight: 700; color: var(--cor); }
        .grade .slot { background: color-mix(in srgb, var(--cor) 12%, #fff); border-color: color-mix(in srgb, var(--cor) 30%, var(--linha)); font-weight: 600; }
        .grade .choque { background: var(--erro-fundo); border-color: color-mix(in srgb, var(--erro) 40%, #fff); color: var(--erro); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; align-items: end; }
        .form-grid label { display: grid; gap: 4px; font-size: .8rem; font-weight: 700; color: var(--suave); }
        .form-grid input, .form-grid select, .form-grid textarea { min-height: 36px; padding: 6px 8px; border: 1px solid var(--linha); border-radius: 8px; font: inherit; background: #fff; }
        .form-grid textarea { min-height: 70px; resize: vertical; }
        .form-grid .col-wide { grid-column: 1 / -1; }
        .form-grid .acoes { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; }
        .timeline { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .timeline li { border: 1px solid var(--linha); border-radius: 12px; padding: 10px 12px; background: #fff; border-left: 4px solid var(--destaque); }
        .timeline li.and { border-left-color: var(--ambar); background: color-mix(in srgb, var(--clara) 40%, #fff); }
        .timeline li header { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; font-size: .82rem; color: var(--suave); margin: 0 0 4px; }
        .timeline li header strong { color: var(--tinta); }
        .timeline li p { margin: 0; }
        .timeline .meta-fluxo { margin-top: 6px !important; font-size: .82rem; color: var(--suave); }
        .anexo-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; color: var(--cor); font-weight: 700; text-decoration: none; }
        footer.rodape { background: var(--nav); color: var(--nav-texto); border-top: 3px solid var(--destaque); padding: 12px 18px; font-size: .82rem; }
        @keyframes sobe { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        @media (max-width: 860px) { .lateral { display: none; } }

    </style>
    @include('buriti-ui::estilos')
</head>
<body>
@php $path = request()->path(); $grupos = $menuGrupos ?? []; @endphp
<div class="app">
    <aside class="lateral">
        <a class="marca" href="{{ url('/solicitacao-servicos') }}" title="{{ $identidade['nome'] ?? '' }}">
            <span class="icone"><i class="fa {{ $identidade['icone'] ?? 'fa-ticket' }}"></i></span>
        </a>
        @foreach($grupos as $grupo)
            <nav class="modulo">
                <div class="modulo-nome">{{ $grupo['nome'] }}</div>
                @foreach($grupo['itens'] as $item)
                    <a href="{{ url($item['url']) }}" class="@if(request()->is(ltrim($item['url'],'/'))) ativo @endif">
                        <i class="fa {{ $item['icone'] }}"></i> {{ $item['rotulo'] }}
                    </a>
                @endforeach
            </nav>
        @endforeach
    </aside>
    <div class="miolo">
        @php
            $homeModulo = url('/solicitacao-servicos');
            $nomeModulo = $identidade['nome'] ?? 'Chamados';
            $descricaoModulo = $identidade['descricao'] ?? null;
            $menuGrupos = $menuGrupos ?? ($grupos ?? []);
        @endphp
        @include('buriti-ui::topo')
        <main>
            @if(session('ok')) <p class="flash ok">{{ session('ok') }}</p> @endif
            @if(session('erro')) <p class="flash erro">{{ session('erro') }}</p> @endif
            <header class="cabeca-tela">
                <div class="identidade-tela">
                    <span class="marca-tela"><i class="fa @yield('icone-tela', $identidade['icone'] ?? 'fa-ticket')"></i></span>
                    <div>
                        <h1>@hasSection('titulo-tela')@yield('titulo-tela')@else@yield('migalha', 'Painel')@endif</h1>
                        @hasSection('lead')<p class="lead">@yield('lead')</p>@endif
                    </div>
                </div>
                <div class="acoes-tela">@yield('acoes-topo')</div>
            </header>
            @yield('conteudo')
        </main>
        <footer class="rodape">Chamados · Laguna · Balcão escola ↔ SME</footer>
    </div>
</div>
</body>
</html>
