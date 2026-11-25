<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

final class SetorChamado
{
    /**
     * @return list<array{id:int,nome:string,descricao:string,ativo:bool,responsaveis:list<array{user_id:int,nome:string}>}>
     */
    public static function listar(bool $somenteAtivos = true): array
    {
        $out = [];
        foreach (PocRegistro::listar('setor', 300) as $r) {
            $p = $r['payload'];
            $ativo = (bool) ($p['ativo'] ?? true);
            if ($somenteAtivos && ! $ativo) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'nome' => (string) ($p['nome'] ?? $r['titulo']),
                'descricao' => (string) ($p['descricao'] ?? ''),
                'ativo' => $ativo,
                'responsaveis' => self::normalizarResponsaveis($p['responsaveis'] ?? []),
            ];
        }

        if ($out === [] && (bool) config('solicitacao.seed_setores_padrao', true) && PocRegistro::disponivel() && PocRegistro::contar('setor') === 0) {
            foreach (self::padrao() as $s) {
                self::gravar($s['nome'], $s['descricao'], [], true);
            }
            foreach (PocRegistro::listar('setor', 300) as $r) {
                $p = $r['payload'];
                $out[] = [
                    'id' => $r['id'],
                    'nome' => (string) ($p['nome'] ?? $r['titulo']),
                    'descricao' => (string) ($p['descricao'] ?? ''),
                    'ativo' => (bool) ($p['ativo'] ?? true),
                    'responsaveis' => self::normalizarResponsaveis($p['responsaveis'] ?? []),
                ];
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
    public static function gravar(string $nome, string $descricao = '', array $responsaveis = [], bool $ativo = true): int
    {
        return PocRegistro::gravar('setor', $nome, $descricao !== '' ? $descricao : null, [
            'nome' => $nome,
            'descricao' => $descricao,
            'ativo' => $ativo,
            'responsaveis' => self::normalizarResponsaveis($responsaveis),
        ]);
    }

    /**
     * @param  list<array{user_id:int,nome:string}>  $responsaveis
     */
    public static function atualizar(int $id, string $nome, string $descricao, array $responsaveis, bool $ativo): bool
    {
        $reg = PocRegistro::buscar($id);
        if ($reg === null || $reg['tipo'] !== 'setor') {
            return false;
        }

        return PocRegistro::atualizarPayload($id, [
            'nome' => $nome,
            'descricao' => $descricao,
            'ativo' => $ativo,
            'responsaveis' => self::normalizarResponsaveis($responsaveis),
        ], $nome, $descricao !== '' ? $descricao : null);
    }

    /**
     * @return list<array{id:int,nome:string,descricao:string,ativo:bool,responsaveis:list<array{user_id:int,nome:string}>}>
     */
    public static function padrao(): array
    {
        return [
            ['id' => 0, 'nome' => 'TI / Informática', 'descricao' => 'Suporte técnico e sistemas', 'ativo' => true, 'responsaveis' => []],
            ['id' => 0, 'nome' => 'Manutenção predial', 'descricao' => 'Elétrica, hidráulica, obras', 'ativo' => true, 'responsaveis' => []],
            ['id' => 0, 'nome' => 'Pedagógico', 'descricao' => 'Demandas pedagógicas e formação', 'ativo' => true, 'responsaveis' => []],
            ['id' => 0, 'nome' => 'Administrativo', 'descricao' => 'Documentos, compras e RH', 'ativo' => true, 'responsaveis' => []],
        ];
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
}
