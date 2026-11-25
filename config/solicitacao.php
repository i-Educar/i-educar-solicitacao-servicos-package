<?php

return [
    'public' => [
        'aparecer_na_home' => false,
        'titulo' => 'Solicitação de serviços',
        'ordem' => 63,
        'url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | SLA por prioridade (em horas) — lido por ChamadoSla::dentroDoPrazo
    |--------------------------------------------------------------------------
    */
    'sla_horas' => [
        'alta' => (int) env('SOLICITACAO_SLA_ALTA', 24),
        'media' => (int) env('SOLICITACAO_SLA_MEDIA', 72),
        'baixa' => (int) env('SOLICITACAO_SLA_BAIXA', 168),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seed demo quando a tabela poc_registro estiver vazia
    |--------------------------------------------------------------------------
    */
    'seed_demo_se_vazio' => (bool) env('SOLICITACAO_SEED_DEMO', true),

    /*
    |--------------------------------------------------------------------------
    | Formulário real na tela "novo"
    |--------------------------------------------------------------------------
    */
    'novo_chamado_ativo' => (bool) env('SOLICITACAO_NOVO_CHAMADO', true),
    'escolas_do_nucleo' => (bool) env('SOLICITACAO_ESCOLAS_NUCLEO', true),

    /*
    |--------------------------------------------------------------------------
    | Anexos PDF (abertura e respostas)
    |--------------------------------------------------------------------------
    */
    'anexo_max_kb' => (int) env('SOLICITACAO_ANEXO_MAX_KB', 5120),
    'seed_setores_padrao' => (bool) env('SOLICITACAO_SEED_SETORES', true),
];
