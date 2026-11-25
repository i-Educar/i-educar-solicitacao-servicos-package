<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

final class TimelineChamado
{
    /**
     * @param  list<int>  $anexoIds
     */
    public static function responder(int $chamadoId, string $texto, string $autor, ?int $autorId = null, array $anexoIds = []): int
    {
        return PocRegistro::gravar('mensagem', mb_substr($texto, 0, 80), 'chamado #'.$chamadoId, [
            'chamado_id' => $chamadoId,
            'tipo' => 'resposta',
            'texto' => $texto,
            'autor' => $autor,
            'autor_id' => $autorId,
            'anexo_ids' => $anexoIds,
            'em' => now()->toIso8601String(),
        ]);
    }

    public static function andamento(
        int $chamadoId,
        string $texto,
        string $autor,
        ?string $deStatus = null,
        ?string $paraStatus = null,
        ?string $setor = null,
        ?string $responsavel = null,
    ): int {
        return PocRegistro::gravar('andamento', mb_substr($texto, 0, 80), 'chamado #'.$chamadoId, [
            'chamado_id' => $chamadoId,
            'tipo' => 'andamento',
            'texto' => $texto,
            'autor' => $autor,
            'de_status' => $deStatus,
            'para_status' => $paraStatus,
            'setor' => $setor,
            'responsavel' => $responsavel,
            'em' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return list<array{id:int,kind:string,texto:string,autor:string,em:?string,anexo_ids:list<int>,de_status:?string,para_status:?string,setor:?string,responsavel:?string}>
     */
    public static function doChamado(int $chamadoId): array
    {
        $itens = [];
        foreach (['mensagem', 'andamento'] as $tipo) {
            foreach (PocRegistro::listar($tipo, 500) as $r) {
                $p = $r['payload'];
                if ((int) ($p['chamado_id'] ?? 0) !== $chamadoId) {
                    continue;
                }
                $itens[] = [
                    'id' => $r['id'],
                    'kind' => $tipo,
                    'texto' => (string) ($p['texto'] ?? $r['titulo']),
                    'autor' => (string) ($p['autor'] ?? 'sistema'),
                    'em' => (string) ($p['em'] ?? $r['created_at'] ?? ''),
                    'anexo_ids' => array_map('intval', (array) ($p['anexo_ids'] ?? [])),
                    'de_status' => isset($p['de_status']) ? (string) $p['de_status'] : null,
                    'para_status' => isset($p['para_status']) ? (string) $p['para_status'] : null,
                    'setor' => isset($p['setor']) ? (string) $p['setor'] : null,
                    'responsavel' => isset($p['responsavel']) ? (string) $p['responsavel'] : null,
                    'created_at' => $r['created_at'],
                ];
            }
        }

        usort($itens, static function (array $a, array $b): int {
            return strcmp((string) ($a['em'] ?: $a['created_at']), (string) ($b['em'] ?: $b['created_at']));
        });

        return $itens;
    }
}
