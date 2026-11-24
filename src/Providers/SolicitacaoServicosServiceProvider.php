<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Providers;

use Illuminate\Support\ServiceProvider;

class SolicitacaoServicosServiceProvider extends ServiceProvider
{
    public const string PACKAGE = 'buriti/i-educar-solicitacao-servicos-package';

    public function register(): void
    {
        // Services são value objects — instanciar com `new` nos callers (não bind no container).
    }

    public function boot(): void
    {
    }
}
