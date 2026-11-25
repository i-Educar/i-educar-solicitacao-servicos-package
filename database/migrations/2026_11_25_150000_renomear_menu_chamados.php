<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\Processos;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Menu::query()->where('process', Processos::RAIZ)->update([
            'title' => 'Chamados',
            'description' => 'Central de chamados escola → SME',
            'icon' => 'fa-ticket',
        ]);

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        Menu::query()->where('process', Processos::RAIZ)->update([
            'title' => 'Solicitação de serviços',
        ]);

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }
};
