<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

final class ChamadoSla
{
    /** @param array{prioridade:string,aberto_em:string,resolvido_em?:?string} $c */
    public static function dentroDoPrazo(array $c, array $horas = ['alta'=>24,'media'=>72,'baixa'=>168], ?string $agora = null): bool {
        $agora = $agora ?? date('c');
        $limiteH = $horas[$c['prioridade']] ?? 72;
        $limite = strtotime($c['aberto_em']) + $limiteH*3600;
        $ref = isset($c['resolvido_em']) && $c['resolvido_em'] ? strtotime($c['resolvido_em']) : strtotime($agora);
        return $ref <= $limite;
    }
}
