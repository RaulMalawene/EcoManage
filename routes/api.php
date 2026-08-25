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
    // /pessoas/exportar tem de vir ANTES do apiResource: senao a rota
    // pessoas/{pessoa} do "show" apanhava "exportar" como se fosse um id.
    Route::get('pessoas/exportar', [PessoaController::class, 'exportar']);
    Route::apiResource('pessoas', PessoaController::class);

    // Materiais e stock (RF-11 a RF-15)
    Route::post('materiais/{material}/stock-inicial', [MaterialController::class, 'stockInicial']);
    Route::post('materiais/{material}/quebra', [MaterialController::class, 'quebra']);
    Route::get('materiais/{material}/quebras', [MaterialController::class, 'quebras']);
    Route::get('materiais/exportar', [MaterialController::class, 'exportar']);
    // Sem ->parameters(), o Laravel tentava singularizar "materiais" (regras
    // de ingles) para "materiai" em vez de "material", e o show/update/destroy
    // nunca faziam bind ao modelo certo — devolviam sempre um registo vazio.
    Route::apiResource('materiais', MaterialController::class)
        ->parameters(['materiais' => 'material']);

    // Compras (RF-16 a RF-18)
    Route::get('compras/exportar', [CompraController::class, 'exportar']);
    Route::apiResource('compras', CompraController::class)->only(['index', 'show', 'store']);

    // Vendas (RF-19 a RF-21)
    Route::post('vendas/{venda}/receber', [VendaController::class, 'receber']);
    Route::get('vendas/exportar', [VendaController::class, 'exportar']);
    Route::apiResource('vendas', VendaController::class)->only(['index', 'show', 'store']);

    // Emprestimos e pagamentos (RF-22 a RF-30)
    Route::post('emprestimos/{emprestimo}/pagar', [EmprestimoController::class, 'pagar']);
    Route::get('emprestimos/exportar', [EmprestimoController::class, 'exportar']);
    Route::apiResource('emprestimos', EmprestimoController::class)->only(['index', 'show', 'store']);

    // Despesas (RF-06)
    Route::get('despesas/exportar', [DespesaController::class, 'exportar']);
    Route::apiResource('despesas', DespesaController::class)->only(['index', 'show', 'store']);

    // Caixa (RF-05 a RF-10) — SO LEITURA
    Route::get('caixa', [CaixaController::class, 'index']);
    Route::get('caixa/saldo', [CaixaController::class, 'saldo']);
    Route::get('caixa/fluxo', [CaixaController::class, 'fluxo']);
    Route::get('caixa/fluxo-mensal', [CaixaController::class, 'fluxoMensal']);
    Route::get('caixa/exportar', [CaixaController::class, 'exportar']);

    // Relatorios — DRE e dashboard (Modulo 11)
    Route::get('relatorios/dre', [RelatorioController::class, 'dre']);
    Route::get('relatorios/dre-mensal', [RelatorioController::class, 'dreMensal']);
    Route::get('relatorios/dre/exportar', [RelatorioController::class, 'exportarDre']);
    Route::get('relatorios/dashboard', [RelatorioController::class, 'dashboard']);

});