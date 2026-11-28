<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Support;

final class Identidade
{
    public const COR = '#c74634';
    public const DESTAQUE = '#e24b35';
    public const COR_CLARA = '#fdeceb';
    public const ICONE = 'fa-ticket';
    public const NOME = 'Chamados';

    public const DESCRICAO = 'Pedidos das escolas à SME, prazos e setores';

    /** @return array{cor:string,destaque:string,clara:string,icone:string,nome:string,descricao:string} */
    public static function tokens(): array
    {
        return [
            'cor' => self::COR,
            'destaque' => self::DESTAQUE,
            'clara' => self::COR_CLARA,
            'icone' => self::ICONE,
            'nome' => self::NOME,
            'descricao' => self::DESCRICAO,
        ];
    }
}
