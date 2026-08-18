<?php

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Formato de resposta consistente para toda a API. Cada endpoint devolve
 * sempre a mesma estrutura, para que a aplicacao/telemovel saiba o que
 * esperar:
 *
 *   Sucesso: { "sucesso": true,  "dados": ..., "mensagem": ... }
 *   Erro:    { "sucesso": false, "mensagem": ..., "erros": ... }
 *
 * Os controllers usam este trait em vez de montarem o JSON a mao.
 */
trait RespostaApi
{
    protected function ok($dados = null, ?string $mensagem = null, int $codigo = 200): JsonResponse
    {
        return response()->json([
            'sucesso' => true,
            'mensagem' => $mensagem,
            'dados' => $dados,
        ], $codigo);
    }

    protected function criado($dados = null, ?string $mensagem = 'Registo criado com sucesso.'): JsonResponse
    {
        return $this->ok($dados, $mensagem, 201);
    }

    protected function erro(string $mensagem, int $codigo = 400, $erros = null): JsonResponse
    {
        return response()->json([
            'sucesso' => false,
            'mensagem' => $mensagem,
            'erros' => $erros,
        ], $codigo);
    }
}