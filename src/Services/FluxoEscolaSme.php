<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

final class FluxoEscolaSme
{
    public static function proximoStatus(string $atual): string {
        return match($atual) {
            'aberto' => 'triagem_sme',
            'triagem_sme' => 'em_atendimento',
            'em_atendimento' => 'resolvido',
            default => $atual,
        };
    }
}
