<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\PessoaRequest;
use App\Http\Resources\PessoaResource;
use App\Models\Pessoa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CRUD de pessoas — fornecedores, clientes e devedores (RF-31, RF-32).
 *
 * O controller e' fino de proposito: valida (via PessoaRequest),
 * fala com o model, devolve no formato consistente (via RespostaApi).
 * Nao ha regras de negocio aqui — pessoas nao precisam de servico.
 */
class PessoaController extends Controller
{
    use RespostaApi;

    /** Lista pessoas, com filtros opcionais por tipo, estado e texto. */
    public function index(Request $request): JsonResponse
    {
        $query = Pessoa::query();

        // Filtro por tipo (?tipo=devedor)
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        // So activas por defeito; ?incluir_inactivas=1 mostra todas
        if (! $request->boolean('incluir_inactivas')) {
            $query->where('activo', true);
        }

        // Pesquisa por nome (?pesquisa=joao)
        if ($request->filled('pesquisa')) {
            $termo = $request->string('pesquisa');
            $query->where('nome', 'like', "%{$termo}%");
        }

        $pessoas = $query->orderBy('nome')->paginate(20);

        return $this->ok([
            'itens' => PessoaResource::collection($pessoas),
            'paginacao' => [
                'total' => $pessoas->total(),
                'pagina' => $pessoas->currentPage(),
                'por_pagina' => $pessoas->perPage(),
                'ultima_pagina' => $pessoas->lastPage(),
            ],
        ]);
    }

    /** Mostra uma pessoa. */
    public function show(Pessoa $pessoa): JsonResponse
    {
        return $this->ok(new PessoaResource($pessoa));
    }

    /** Cria uma pessoa. */
    public function store(PessoaRequest $request): JsonResponse
    {
        $pessoa = Pessoa::create($request->validated());

        return $this->criado(new PessoaResource($pessoa), 'Pessoa criada com sucesso.');
    }

    /** Actualiza uma pessoa. */
    public function update(PessoaRequest $request, Pessoa $pessoa): JsonResponse
    {
        $pessoa->update($request->validated());

        return $this->ok(new PessoaResource($pessoa), 'Pessoa actualizada com sucesso.');
    }

    /**
     * Desactiva uma pessoa (nao apaga). Uma pessoa com registos associados
     * nunca deve ser apagada — so marcada inactiva (nota de implementacao 6).
     */
    public function destroy(Pessoa $pessoa): JsonResponse
    {
        $pessoa->update(['activo' => false]);

        return $this->ok(null, 'Pessoa desactivada com sucesso.');
    }

    /**
     * Exporta os contactos em CSV. Mesma construcao de query do index()
     * (mesmos filtros, mesma ordenacao) — so troca a paginacao por um
     * limite de seguranca de 1000 linhas e o JSON por um ficheiro CSV
     * em streaming, sem depender de nenhum pacote extra.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $query = Pessoa::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        if (! $request->boolean('incluir_inactivas')) {
            $query->where('activo', true);
        }

        if ($request->filled('pesquisa')) {
            $termo = $request->string('pesquisa');
            $query->where('nome', 'like', "%{$termo}%");
        }

        $pessoas = $query->orderBy('nome')->limit(1000)->get();

        return response()->streamDownload(function () use ($pessoas) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8: sem isto o Excel mostra os acentos trocados.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Nome', 'Tipo', 'Telefone', 'Observacoes', 'Activo']);

            foreach ($pessoas as $p) {
                fputcsv($out, [$p->nome, $p->tipo->rotulo(), $p->telefone, $p->observacoes, $p->activo ? 'Sim' : 'Nao']);
            }

            fclose($out);
        }, 'contactos-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}