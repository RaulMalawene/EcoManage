<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaixaController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\DespesaController;
use App\Http\Controllers\Api\EmprestimoController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\PessoaController;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\VendaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da API — EcoManage
|--------------------------------------------------------------------------
| Prefixo /api automatico. Rotas publicas em cima; abaixo, tudo o que
| exige token Sanctum (auth:sanctum).
*/

// --- Publicas ---------------------------------------------------------

Route::post('/login', [AuthController::class, 'login']);

// --- Protegidas (exigem token) ----------------------------------------

Route::middleware('auth:sanctum')->group(function () {

    // Autenticacao
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Pessoas (RF-31, RF-32)
    Route::apiResource('pessoas', PessoaController::class);

    // Materiais e stock (RF-11 a RF-15)
    Route::post('materiais/{material}/stock-inicial', [MaterialController::class, 'stockInicial']);
    Route::apiResource('materiais', MaterialController::class);

    // Compras (RF-16 a RF-18)
    Route::apiResource('compras', CompraController::class)->only(['index', 'show', 'store']);

    // Vendas (RF-19 a RF-21)
    Route::post('vendas/{venda}/receber', [VendaController::class, 'receber']);
    Route::apiResource('vendas', VendaController::class)->only(['index', 'show', 'store']);

    // Emprestimos e pagamentos (RF-22 a RF-30)
    Route::post('emprestimos/{emprestimo}/pagar', [EmprestimoController::class, 'pagar']);
    Route::apiResource('emprestimos', EmprestimoController::class)->only(['index', 'show', 'store']);

    // Despesas (RF-06)
    Route::apiResource('despesas', DespesaController::class)->only(['index', 'show', 'store']);

    // Caixa (RF-05 a RF-10) — SO LEITURA
    Route::get('caixa', [CaixaController::class, 'index']);
    Route::get('caixa/saldo', [CaixaController::class, 'saldo']);
    Route::get('caixa/fluxo', [CaixaController::class, 'fluxo']);

    // Relatorios — DRE e dashboard (Modulo 11)
    Route::get('relatorios/dre', [RelatorioController::class, 'dre']);
    Route::get('relatorios/dashboard', [RelatorioController::class, 'dashboard']);

});