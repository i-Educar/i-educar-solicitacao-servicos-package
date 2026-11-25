<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

/** @deprecated Use FluxoChamado */
final class FluxoEscolaSme
{
    public static function proximoStatus(string $atual): string
    {
        $prox = FluxoChamado::proximos($atual);

        return $prox[0] ?? $atual;
    }
}
