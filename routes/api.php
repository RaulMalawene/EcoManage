<?php

use App\Http\Controllers\Api\AuthController;
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

    // (Os proximos modulos — pessoas, materiais, compras, vendas,
    //  emprestimos, caixa, dre — entram aqui.)

});