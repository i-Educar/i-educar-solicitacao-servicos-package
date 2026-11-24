<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Providers;

use iEducar\Packages\BuritiSolicitacaoServicos\Services\ChamadoSla;
use iEducar\Packages\BuritiSolicitacaoServicos\Services\FluxoEscolaSme;
use Illuminate\Support\ServiceProvider;

class SolicitacaoServicosServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-solicitacao-servicos-package';

    public function register(): void
    {
        $this->app->singleton(ChamadoSla::class);
        $this->app->singleton(FluxoEscolaSme::class);
    }

    public function boot(): void
    {
    }
}
