<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PessoaController;
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
    // apiResource cria: GET index, POST store, GET show, PUT/PATCH update, DELETE destroy
    Route::apiResource('pessoas', PessoaController::class);

    // (Proximos: materiais, compras, vendas, emprestimos, caixa, dre.)

});