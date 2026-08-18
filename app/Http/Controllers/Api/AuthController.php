<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticacao (RF-01). Login por username, devolve um token Sanctum
 * que o cliente envia depois em cada pedido no cabecalho:
 *   Authorization: Bearer <token>
 *
 * Nao ha registo publico: as contas sao criadas pelo dono (RF-03).
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $user = User::where('username', $dados['username'])->first();

        // Mensagem generica de proposito: nao revela se foi o utilizador
        // ou a palavra-passe que estava errado (evita adivinhacao).
        if (! $user || ! Hash::check($dados['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Credenciais invalidas.'],
            ]);
        }

        // Uma conta desactivada nao entra (RF-03).
        if (! $user->activo) {
            throw ValidationException::withMessages([
                'username' => ['Esta conta esta desactivada. Contacte o administrador.'],
            ]);
        }

        // Um token por sessao de login. O nome ajuda a distinguir
        // dispositivos ('telemovel-do-dono', etc.) — por agora fixo.
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'utilizador' => new UserResource($user),
        ]);
    }

    /** Termina a sessao: apaga o token usado neste pedido. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensagem' => 'Sessao terminada.']);
    }

    /** Dados do utilizador autenticado — util para a app saber quem entrou. */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'utilizador' => new UserResource($request->user()),
        ]);
    }
}