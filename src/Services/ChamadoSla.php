<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

final class ChamadoSla
{
    /** @param array{prioridade:string,aberto_em:string,resolvido_em?:?string} $c */
    public static function dentroDoPrazo(array $c, ?array $horas = null, ?string $agora = null): bool
    {
        $horas ??= self::horasPadrao();
        $agora = $agora ?? date('c');
        $limiteH = (int) ($horas[$c['prioridade']] ?? 72);
        $limite = strtotime($c['aberto_em']) + $limiteH * 3600;
        $ref = isset($c['resolvido_em']) && $c['resolvido_em']
            ? strtotime($c['resolvido_em'])
            : strtotime($agora);

        return $ref <= $limite;
    }

    /** @return array<string,int> */
    public static function horasPadrao(): array
    {
        $cfg = function_exists('config') ? (array) config('solicitacao.sla_horas', []) : [];

        return [
            'alta' => (int) ($cfg['alta'] ?? 24),
            'media' => (int) ($cfg['media'] ?? 72),
            'baixa' => (int) ($cfg['baixa'] ?? 168),
        ];
    }
}
