<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

final class ChamadoSla
{
    /**
     * @param  array{prioridade?:string,aberto_em:string,resolvido_em?:?string,sla_horas?:int|null}  $c
     */
    public static function dentroDoPrazo(array $c, ?array $horas = null, ?string $agora = null): bool
    {
        $horas ??= self::horasPadrao();
        $agora = $agora ?? date('c');
        $limiteH = self::horasDoChamado($c, $horas);
        $limite = strtotime($c['aberto_em']) + $limiteH * 3600;
        $ref = isset($c['resolvido_em']) && $c['resolvido_em']
            ? strtotime($c['resolvido_em'])
            : strtotime($agora);

        return $ref <= $limite;
    }

    /**
     * SLA do chamado: horas do setor (quando gravadas) ou mapa por prioridade.
     *
     * @param  array{prioridade?:string,sla_horas?:int|null}  $c
     * @param  array<string,int>|null  $horas
     */
    public static function horasDoChamado(array $c, ?array $horas = null): int
    {
        $setorSla = (int) ($c['sla_horas'] ?? 0);
        if ($setorSla > 0) {
            return $setorSla;
        }
        $horas ??= self::horasPadrao();

        return (int) ($horas[(string) ($c['prioridade'] ?? 'media')] ?? 72);
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
