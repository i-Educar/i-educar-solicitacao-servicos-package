<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Console;

use iEducar\Packages\BuritiSolicitacaoServicos\Services\SolicitacaoDemoService;
use Illuminate\Console\Command;

class SolicitacaoDemoCommand extends Command
{
    protected $signature = 'solicitacao:demo {--force : Recria chamados demo}';

    protected $description = 'PoC — povoa chamados em todas as etapas do fluxo com atores distintos';

    public function handle(SolicitacaoDemoService $demo): int
    {
        $this->info('Solicitação de serviços · carga demo multi-etapa');
        $r = $demo->executar((bool) $this->option('force'));
        if ($r['pulado']) {
            $this->warn('  '.$r['mensagem']);

            return self::SUCCESS;
        }
        if (str_contains($r['mensagem'], 'ausente')) {
            $this->error('  '.$r['mensagem']);

            return self::FAILURE;
        }
        $this->line('  '.$r['mensagem']);

        return self::SUCCESS;
    }
}
