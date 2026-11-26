<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Providers;

use iEducar\Packages\BuritiSolicitacaoServicos\Console\SolicitacaoDemoCommand;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\Acesso;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\Identidade;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\MenuFluxo;
use iEducar\Packages\BuritiSolicitacaoServicos\Support\Processos;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SolicitacaoServicosServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-solicitacao-servicos-package';

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/solicitacao.php', 'solicitacao');
        // Fora de runningInConsole(): o check do Ambiente Laguna (HTTP) usa Artisan::all().
        $this->commands([SolicitacaoDemoCommand::class]);
    }

    public function boot(): void
    {
        if (class_exists(\iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::class)) {
            \iEducar\Packages\BuritiLagunaAmbiente\Support\BuritiUi::registrar();
        }
        $root = dirname(__DIR__, 2);
        $this->loadViewsFrom($root.'/resources/views', 'solicitacao');

        if ($this->app->runningInConsole()) {
            if (\App\Support\Tenancy\PackageTenant::allows(self::PACKAGE)) {
                $this->loadMigrationsFrom($root.'/database/migrations');
            }
        }

        Route::middleware([
            'web',
            'ieducar.navigation',
            'ieducar.footer',
            'ieducar.xssbypass',
            'ieducar.suspended',
            'auth',
            'ieducar.checkresetpassword',
        ])->group($root.'/routes/web.php');

        View::composer('solicitacao::*', function ($view): void {
            $view->with('identidade', Identidade::tokens());
            $view->with('menuGrupos', MenuFluxo::grupos([
                'categorias' => Acesso::permite(Processos::CATEGORIAS),
                'setores' => Acesso::permite(Processos::SETORES) || Acesso::permite(Processos::CATEGORIAS),
                'painel' => Acesso::permite(Processos::PAINEL),
                'abertos' => Acesso::permite(Processos::ABERTOS),
                'sla' => Acesso::permite(Processos::SLA),
                'novo' => Acesso::permite(Processos::NOVO),
                'relatorios' => Acesso::permite(Processos::RELATORIOS),
                'auditoria' => Acesso::permite(Processos::AUDITORIA),
            ]));
        });
    }
}
