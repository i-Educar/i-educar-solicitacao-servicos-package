<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Support;

final class MenuFluxo
{
    /**
     * @param  array<string, bool>  $permite
     * @return list<array{nome:string,itens:list<array{rotulo:string,url:string,icone:string,chave:string}>}>
     */
    public static function grupos(array $permite): array
    {
        $item = static function (string $chave, string $rotulo, string $url, string $icone) use ($permite): ?array {
            if (! ($permite[$chave] ?? true)) {
                return null;
            }

            return compact('chave', 'rotulo', 'url', 'icone');
        };
        $filtrar = static fn (array $itens): array => array_values(array_filter($itens));

        return array_values(array_filter([
            [
                'nome' => 'Pedidos de serviço',
                'itens' => $filtrar([
                    $item('painel', 'Início', '/solicitacao-servicos', 'fa-th-large'),
                    $item('novo', 'Abrir novo pedido', '/solicitacao-servicos/novo', 'fa-plus-circle'),
                    $item('abertos', 'Pedidos em andamento', '/solicitacao-servicos/abertos', 'fa-folder-open'),
                    $item('sla', 'Prazos de atendimento', '/solicitacao-servicos/sla', 'fa-clock-o'),
                ]),
            ],
            [
                'nome' => 'Organização',
                'itens' => $filtrar([
                    $item('setores', 'Setores e responsáveis', '/solicitacao-servicos/setores', 'fa-sitemap'),
                    $item('categorias', 'Tipos de pedido', '/solicitacao-servicos/categorias', 'fa-tags'),
                ]),
            ],
            [
                'nome' => 'Consultas',
                'itens' => $filtrar([
                    $item('relatorios', 'Indicadores', '/solicitacao-servicos/relatorios', 'fa-bar-chart'),
                    $item('auditoria', 'Histórico de alterações', '/solicitacao-servicos/auditoria', 'fa-history'),
                ]),
            ],
        ], static fn (array $g): bool => $g['itens'] !== []));
    }
}
