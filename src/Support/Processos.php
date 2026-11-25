<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Support;

final class Processos
{
    public const int RAIZ = 881700;

    public const int PAINEL = 881701;

    public const int ABERTOS = 881702;

    public const int SLA = 881703;

    public const int NOVO = 881704;

    /** Cadastro de categorias / tipos de serviço. */
    public const int CATEGORIAS = 881710;

    public const int RELATORIOS = 881720;

    public const int AUDITORIA = 881730;

    public const int DEFERIR = 881740;

    public const int INDEFERIR = 881741;

    public const int SETORES = 881750;

    public const int RESPONDER = 881751;

    public const int ENCAMINHAR = 881752;

    /** @return array<int, string> */
    public static function titulos(): array
    {
        return [
            self::RAIZ => 'Chamados',
            self::PAINEL => 'Painel',
            self::ABERTOS => 'Chamados',
            self::SLA => 'SLA',
            self::NOVO => 'Novo chamado',
            self::CATEGORIAS => 'Categorias',
            self::RELATORIOS => 'Relatórios',
            self::AUDITORIA => 'Auditoria',
            self::DEFERIR => 'Resolver / fechar',
            self::INDEFERIR => 'Indeferir chamado',
            self::SETORES => 'Setores e responsáveis',
            self::RESPONDER => 'Responder chamado',
            self::ENCAMINHAR => 'Encaminhar chamado',
        ];
    }

    /** @return list<int> */
    public static function todos(): array
    {
        return array_keys(self::titulos());
    }
}
