<?php

use App\Exceptions\RegraNegocioException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API pura: nao ha nenhuma rota web chamada "login". Por omissao o
        // Laravel regista redirectGuestsTo(route('login')), o que faz o
        // Authenticate::redirectTo() rebentar com RouteNotFoundException
        // sempre que um pedido sem token chega a uma rota protegida (o
        // AuthenticationException so leva redirectTo=null quando o pedido
        // ja "expectsJson()", e o curl/apps moveis nem sempre mandam
        // Accept: application/json). Sem alvo de redirecionamento, o
        // AuthenticationException segue sempre para o render() de baixo,
        // que devolve 401 em JSON.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Todos os erros das rotas /api/* saem no formato consistente
        // { sucesso, mensagem, erros }, em vez do HTML/JSON cru do Laravel.

        // Regra de negocio (ex.: stock insuficiente) -> 422
        $exceptions->render(function (RegraNegocioException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => $e->getMessage(),
                    'erros' => null,
                ], 422);
            }
        });

        // Erros de validacao -> 422 com a lista de campos
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Os dados enviados sao invalidos.',
                    'erros' => $e->errors(),
                ], 422);
            }
        });

        // Registo nao encontrado -> 404
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Registo nao encontrado.',
                    'erros' => null,
                ], 404);
            }
        });

        // Rota inexistente -> 404
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Recurso nao encontrado.',
                    'erros' => null,
                ], 404);
            }
        });

        // Sem token / token invalido -> 401
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Nao autenticado. Faca login primeiro.',
                    'erros' => null,
                ], 401);
            }
        });
    })->create();