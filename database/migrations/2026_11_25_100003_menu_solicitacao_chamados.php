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
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();
        if ($raiz === null) {
            return;
        }

        Menu::query()->updateOrCreate(['process' => Processos::RAIZ], [
            'description' => 'Central de chamados: respostas, andamentos, setores, anexos PDF e feedback',
        ]);

        $cadastros = Menu::query()
            ->where('parent_id', $raiz->getKey())
            ->where('title', 'Cadastros')
            ->first();

        $paiCad = $cadastros?->getKey() ?? $raiz->getKey();

        $novos = [
            [Processos::SETORES, $paiCad, 'Setores / responsáveis', '/solicitacao-servicos/setores', 'Encaminhamento e donos', 2],
            [Processos::RESPONDER, $raiz->getKey(), 'Responder', '/solicitacao-servicos/abertos', 'Permissão de resposta e andamento', 90],
            [Processos::ENCAMINHAR, $raiz->getKey(), 'Encaminhar', '/solicitacao-servicos/abertos', 'Permissão de encaminhar / assumir', 91],
        ];

        foreach ($novos as [$processo, $pai, $titulo, $link, $descricao, $ordem]) {
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

        Menu::query()->where('process', Processos::ABERTOS)->update([
            'title' => 'Chamados',
            'description' => 'Fila, respostas e andamentos',
        ]);
        Menu::query()->where('process', Processos::DEFERIR)->update([
            'title' => 'Resolver / fechar',
            'description' => 'Marcar resolvido e alterar fluxo',
        ]);

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario']);
        foreach ($tipos as $tipo) {
            foreach ([Processos::SETORES, Processos::RESPONDER, Processos::ENCAMINHAR] as $processo) {
                $menuId = Menu::query()->where('process', $processo)->value('id');
                if ($menuId === null) {
                    continue;
                }
                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    ['cadastra' => 1, 'visualiza' => 1, 'exclui' => 0],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        foreach ([Processos::SETORES, Processos::RESPONDER, Processos::ENCAMINHAR] as $processo) {
            $menu = Menu::query()->where('process', $processo)->first();
            if ($menu === null) {
                continue;
            }
            DB::table('pmieducar.menu_tipo_usuario')->where('menu_id', $menu->getKey())->delete();
            $menu->delete();
        }
    }
};
