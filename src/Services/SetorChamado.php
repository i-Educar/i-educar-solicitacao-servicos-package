<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

final class SetorChamado
{
    public const int SLA_PADRAO = 72;

    /**
     * @return list<array{id:int,nome:string,descricao:string,ativo:bool,sla_horas:int,responsaveis:list<array{user_id:int,nome:string}>}>
     */
    public static function listar(bool $somenteAtivos = true): array
    {
        $out = [];
        foreach (PocRegistro::listar('setor', 300) as $r) {
            $out[] = self::deRegistro($r);
        }
        $out = array_values(array_filter(
            $out,
            static fn (array $s): bool => $somenteAtivos ? $s['ativo'] : true
        ));

        if ($out === [] && (bool) config('solicitacao.seed_setores_padrao', true) && PocRegistro::disponivel() && PocRegistro::contar('setor') === 0) {
            foreach (self::padrao() as $s) {
                self::gravar($s['nome'], $s['descricao'], [], true, $s['sla_horas']);
            }
            $out = [];
            foreach (PocRegistro::listar('setor', 300) as $r) {
                $out[] = self::deRegistro($r);
            }
        }

        return $out;
    }

    public static function buscar(int $id): ?array
    {
        foreach (self::listar(false) as $s) {
            if ($s['id'] === $id) {
                return $s;
            }
        }

        return null;
    }

    /**
     * @param  list<array{user_id:int,nome:string}>  $responsaveis
     */
    public static function gravar(
        string $nome,
        string $descricao = '',
        array $responsaveis = [],
        bool $ativo = true,
        int $slaHoras = self::SLA_PADRAO,
    ): int {
        $slaHoras = self::normalizarSla($slaHoras);

        return PocRegistro::gravar('setor', $nome, self::meta($descricao, $slaHoras), [
            'nome' => $nome,
            'descricao' => $descricao,
            'ativo' => $ativo,
            'sla_horas' => $slaHoras,
            'responsaveis' => self::normalizarResponsaveis($responsaveis),
        ]);
    }

    /**
     * @param  list<array{user_id:int,nome:string}>  $responsaveis
     */
    public static function atualizar(
        int $id,
        string $nome,
        string $descricao,
        array $responsaveis,
        bool $ativo,
        int $slaHoras = self::SLA_PADRAO,
    ): bool {
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'setor') {
            return false;
        }
        $slaHoras = self::normalizarSla($slaHoras);

        return PocRegistro::atualizarPayload($id, [
            'nome' => $nome,
            'descricao' => $descricao,
            'ativo' => $ativo,
            'sla_horas' => $slaHoras,
            'responsaveis' => self::normalizarResponsaveis($responsaveis),
        ], $nome, self::meta($descricao, $slaHoras));
    }

    /**
     * @return list<array{id:int,nome:string,descricao:string,ativo:bool,sla_horas:int,responsaveis:list<array{user_id:int,nome:string}>}>
     */
    public static function padrao(): array
    {
        return [
            ['id' => 0, 'nome' => 'TI / Informática', 'descricao' => 'Suporte técnico e sistemas', 'ativo' => true, 'sla_horas' => 48, 'responsaveis' => []],
            ['id' => 0, 'nome' => 'Manutenção predial', 'descricao' => 'Elétrica, hidráulica, obras', 'ativo' => true, 'sla_horas' => 24, 'responsaveis' => []],
            ['id' => 0, 'nome' => 'Pedagógico', 'descricao' => 'Demandas pedagógicas e formação', 'ativo' => true, 'sla_horas' => 72, 'responsaveis' => []],
            ['id' => 0, 'nome' => 'Administrativo', 'descricao' => 'Documentos, compras e RH', 'ativo' => true, 'sla_horas' => 72, 'responsaveis' => []],
        ];
    }

    public static function normalizarSla(mixed $raw): int
    {
        $n = (int) $raw;

        return max(1, min(8760, $n > 0 ? $n : self::SLA_PADRAO));
    }

    /**
     * @param  mixed  $raw
     * @return list<array{user_id:int,nome:string}>
     */
    public static function normalizarResponsaveis(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $nome = trim((string) ($item['nome'] ?? ''));
            if ($nome === '') {
                continue;
            }
            $out[] = [
                'user_id' => (int) ($item['user_id'] ?? 0),
                'nome' => $nome,
            ];
        }

        return $out;
    }

    /**
     * @param  array{id:int,tipo:string,titulo:string,meta:?string,payload:array<string,mixed>}  $r
     * @return array{id:int,nome:string,descricao:string,ativo:bool,sla_horas:int,responsaveis:list<array{user_id:int,nome:string}>}
     */
    private static function deRegistro(array $r): array
    {
        $p = $r['payload'];

        return [
            'id' => $r['id'],
            'nome' => (string) ($p['nome'] ?? $r['titulo']),
            'descricao' => (string) ($p['descricao'] ?? ''),
            'ativo' => (bool) ($p['ativo'] ?? true),
            'sla_horas' => self::normalizarSla($p['sla_horas'] ?? self::SLA_PADRAO),
            'responsaveis' => self::normalizarResponsaveis($p['responsaveis'] ?? []),
        ];
    }

    private static function meta(string $descricao, int $slaHoras): string
    {
        $base = $descricao !== '' ? $descricao : 'SLA '.$slaHoras.'h';

        return str_contains($base, 'SLA') ? $base : $base.' · SLA '.$slaHoras.'h';
    }
}
