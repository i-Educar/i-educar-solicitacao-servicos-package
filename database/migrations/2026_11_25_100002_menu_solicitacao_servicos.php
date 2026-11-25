<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $raiz = Menu::query()->updateOrCreate(['process' => Processos::RAIZ], [
            'parent_id' => null,
            'title' => 'Chamados',
            'description' => 'Chamados escola → SME com SLA, relatórios, auditoria e deferimento',
            'link' => '/solicitacao-servicos',
            'icon' => 'fa-ticket',
            'order' => 12,
            'type' => 1,
            'active' => true,
        ]);

        $cadastros = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Cadastros'],
            ['order' => 1, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );
        $operacao = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Operação'],
            ['order' => 2, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );
        $relatorios = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Relatórios'],
            ['order' => 3, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );
        $auditoria = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Auditoria'],
            ['order' => 4, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );
        $decisao = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Decisão'],
            ['order' => 5, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );

        $links = [
            [Processos::CATEGORIAS, $cadastros->getKey(), 'Categorias', '/solicitacao-servicos/categorias', 'Tipos de serviço e SLA', 1],
            [Processos::PAINEL, $operacao->getKey(), 'Painel', '/solicitacao-servicos', 'Visão geral', 1],
            [Processos::ABERTOS, $operacao->getKey(), 'Abertos', '/solicitacao-servicos/abertos', 'Em andamento', 2],
            [Processos::SLA, $operacao->getKey(), 'SLA', '/solicitacao-servicos/sla', 'Prazos e atrasos', 3],
            [Processos::NOVO, $operacao->getKey(), 'Novo chamado', '/solicitacao-servicos/novo', 'Abrir solicitação', 4],
            [Processos::RELATORIOS, $relatorios->getKey(), 'Andamento', '/solicitacao-servicos/relatorios', 'Consolidados', 1],
            [Processos::AUDITORIA, $auditoria->getKey(), 'Histórico', '/solicitacao-servicos/auditoria', 'Trilha de decisões', 1],
            [Processos::DEFERIR, $decisao->getKey(), 'Deferir', '/solicitacao-servicos/abertos', 'Permissão de deferimento', 1],
            [Processos::INDEFERIR, $decisao->getKey(), 'Indeferir', '/solicitacao-servicos/abertos', 'Permissão de indeferimento', 2],
        ];

        foreach ($links as [$processo, $pai, $titulo, $link, $descricao, $ordem]) {
            Menu::query()->updateOrCreate(['process' => $processo], [
                'parent_id' => $pai,
                'title' => $titulo,
                'description' => $descricao,
                'link' => $link,
                'order' => $ordem,
                'type' => 3,
                'active' => true,
            ]);
        }

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario']);
        foreach ($tipos as $tipo) {
            foreach (Processos::todos() as $processo) {
                $menuId = Menu::query()->where('process', $processo)->value('id');
                if ($menuId === null) {
                    continue;
                }
                $cadastra = in_array($processo, [
                    Processos::NOVO,
                    Processos::CATEGORIAS,
                    Processos::DEFERIR,
                    Processos::INDEFERIR,
                ], true) ? 1 : 0;
                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    ['cadastra' => $cadastra, 'visualiza' => 1, 'exclui' => 0],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();
        if ($raiz === null) {
            return;
        }
        $grupos = Menu::query()->where('parent_id', $raiz->getKey())->pluck('id');
        $folhas = Menu::query()->whereIn('parent_id', $grupos)->pluck('id');
        $ids = $grupos->merge($folhas)->push($raiz->getKey());
        DB::table('pmieducar.menu_tipo_usuario')->whereIn('menu_id', $ids)->delete();
        Menu::query()->whereIn('id', $folhas)->delete();
        Menu::query()->whereIn('id', $grupos)->delete();
        $raiz->delete();
        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }
};
