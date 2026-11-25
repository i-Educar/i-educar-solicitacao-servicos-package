<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AnexoPdf
{
    public const string DISK = 'local';

    public const string PREFIXO = 'solicitacao_servicos/anexos';

    public static function maxKb(): int
    {
        return max(256, (int) config('solicitacao.anexo_max_kb', 5120));
    }

    /**
     * @return array{id:int,nome:string,path:string,tamanho:int,chamado_id:int,mensagem_id:?int}|null
     */
    public static function salvar(UploadedFile $file, int $chamadoId, ?int $mensagemId = null, ?string $autor = null): ?array
    {
        if (! PocRegistro::disponivel()) {
            return null;
        }
        if (strtolower($file->getClientOriginalExtension()) !== 'pdf'
            && $file->getMimeType() !== 'application/pdf') {
            throw new \InvalidArgumentException('Apenas PDF é permitido.');
        }
        if ($file->getSize() > self::maxKb() * 1024) {
            throw new \InvalidArgumentException('PDF excede o tamanho máximo ('.self::maxKb().' KB).');
        }

        $nomeOriginal = $file->getClientOriginalName();
        $nomeSeguro = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME)) ?: 'anexo';
        $relativo = $file->storeAs(
            self::PREFIXO.'/'.$chamadoId,
            Str::uuid()->toString().'-'.$nomeSeguro.'.pdf',
            self::DISK
        );
        if ($relativo === false) {
            throw new \RuntimeException('Falha ao gravar o PDF.');
        }

        $id = PocRegistro::gravar('anexo', $nomeOriginal, 'chamado #'.$chamadoId, [
            'chamado_id' => $chamadoId,
            'mensagem_id' => $mensagemId,
            'nome' => $nomeOriginal,
            'path' => $relativo,
            'mime' => 'application/pdf',
            'tamanho' => (int) $file->getSize(),
            'autor' => $autor,
            'em' => now()->toIso8601String(),
        ]);

        return [
            'id' => $id,
            'nome' => $nomeOriginal,
            'path' => $relativo,
            'tamanho' => (int) $file->getSize(),
            'chamado_id' => $chamadoId,
            'mensagem_id' => $mensagemId,
        ];
    }

    /**
     * @return list<array{id:int,nome:string,path:string,tamanho:int,chamado_id:int,mensagem_id:?int,autor:?string,created_at:?string}>
     */
    public static function doChamado(int $chamadoId): array
    {
        $out = [];
        foreach (PocRegistro::listar('anexo', 500) as $r) {
            $p = $r['payload'];
            if ((int) ($p['chamado_id'] ?? 0) !== $chamadoId) {
                continue;
            }
            $out[] = [
                'id' => $r['id'],
                'nome' => (string) ($p['nome'] ?? $r['titulo']),
                'path' => (string) ($p['path'] ?? ''),
                'tamanho' => (int) ($p['tamanho'] ?? 0),
                'chamado_id' => $chamadoId,
                'mensagem_id' => isset($p['mensagem_id']) ? (int) $p['mensagem_id'] : null,
                'autor' => isset($p['autor']) ? (string) $p['autor'] : null,
                'created_at' => $r['created_at'],
            ];
        }

        return $out;
    }

    public static function caminhoAbsoluto(int $anexoId): ?string
    {
        $reg = PocRegistro::buscar($anexoId);
        if ($reg === null || $reg['tipo'] !== 'anexo') {
            return null;
        }
        $path = (string) ($reg['payload']['path'] ?? '');
        if ($path === '' || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->path($path);
    }

    public static function meta(int $anexoId): ?array
    {
        $reg = PocRegistro::buscar($anexoId);
        if ($reg === null || $reg['tipo'] !== 'anexo') {
            return null;
        }
        $p = $reg['payload'];

        return [
            'id' => $reg['id'],
            'nome' => (string) ($p['nome'] ?? $reg['titulo']),
            'chamado_id' => (int) ($p['chamado_id'] ?? 0),
            'path' => (string) ($p['path'] ?? ''),
        ];
    }
}
