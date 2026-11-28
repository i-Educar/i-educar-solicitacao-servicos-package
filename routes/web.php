<?php

use iEducar\Packages\BuritiSolicitacaoServicos\Http\Controllers\SolicitacaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('solicitacao-servicos')->group(function (): void {
    Route::get('/', [SolicitacaoController::class, 'index']);
    Route::get('/abertos', [SolicitacaoController::class, 'abertos']);
    Route::get('/sla', [SolicitacaoController::class, 'sla']);
    Route::get('/novo', [SolicitacaoController::class, 'novo']);
    Route::post('/novo', [SolicitacaoController::class, 'salvarChamado']);

    Route::get('/categorias', [SolicitacaoController::class, 'categorias']);
    Route::post('/categorias', [SolicitacaoController::class, 'salvarCategoria']);

    Route::get('/setores', [SolicitacaoController::class, 'setores']);
    Route::post('/setores', [SolicitacaoController::class, 'salvarSetor']);
    Route::get('/setores/{id}/editar', [SolicitacaoController::class, 'editarSetor'])->whereNumber('id');
    Route::post('/setores/{id}', [SolicitacaoController::class, 'atualizarSetor'])->whereNumber('id');

    Route::get('/relatorios', [SolicitacaoController::class, 'relatorios']);
    Route::post('/relatorios/exportar', [SolicitacaoController::class, 'exportarRelatorio']);
    Route::get('/auditoria', [SolicitacaoController::class, 'auditoria']);

    Route::get('/chamado/{id}', [SolicitacaoController::class, 'detalhe'])->whereNumber('id');
    Route::post('/chamado/{id}/responder', [SolicitacaoController::class, 'responder'])->whereNumber('id');
    Route::post('/chamado/{id}/andamento', [SolicitacaoController::class, 'registrarAndamento'])->whereNumber('id');
    Route::post('/chamado/{id}/encaminhar', [SolicitacaoController::class, 'encaminhar'])->whereNumber('id');
    Route::post('/chamado/{id}/status', [SolicitacaoController::class, 'alterarStatus'])->whereNumber('id');
    Route::post('/chamado/{id}/assumir', [SolicitacaoController::class, 'assumir'])->whereNumber('id');
    Route::post('/chamado/{id}/feedback', [SolicitacaoController::class, 'feedback'])->whereNumber('id');
    Route::post('/chamado/{id}/deferir', [SolicitacaoController::class, 'deferir'])->whereNumber('id');
    Route::post('/chamado/{id}/indeferir', [SolicitacaoController::class, 'indeferir'])->whereNumber('id');

    Route::get('/anexo/{id}', [SolicitacaoController::class, 'baixarAnexo'])->whereNumber('id');
});
