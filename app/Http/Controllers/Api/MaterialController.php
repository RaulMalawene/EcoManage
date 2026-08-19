<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\MaterialRequest;
use App\Http\Requests\StockInicialRequest;
use App\Http\Resources\MaterialResource;
use App\Models\Material;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD de materiais e stock (Modulo 3, RF-11 a RF-15).
 *
 * O CRUD e' simples como o das pessoas. O extra e' o endpoint de stock
 * inicial, que usa o StockService para dar entrada do material que ja
 * existia em armazem quando o sistema arrancou.
 */
class MaterialController extends Controller
{
    use RespostaApi;

    public function index(Request $request): JsonResponse
    {
        $query = Material::query();

        if (! $request->boolean('incluir_inactivos')) {
            $query->where('activo', true);
        }

        if ($request->filled('pesquisa')) {
            $termo = $request->string('pesquisa');
            $query->where('nome', 'like', "%{$termo}%");
        }

        // ?em_alerta=1 mostra so os materiais prontos a vender (RF-15)
        if ($request->boolean('em_alerta')) {
            $query->whereNotNull('limite_alerta_kg')
                ->whereColumn('stock_kg', '>=', 'limite_alerta_kg');
        }

        $materiais = $query->orderBy('nome')->get();

        // Totais uteis para o painel: valor total do stock (RF-14)
        $valorStockTotal = $materiais->sum(
            fn ($m) => (float) $m->stock_kg * (float) $m->custo_medio_kg
        );

        return $this->ok([
            'itens' => MaterialResource::collection($materiais),
            'resumo' => [
                'total_materiais' => $materiais->count(),
                'valor_stock_total' => round($valorStockTotal, 2),
            ],
        ]);
    }

    public function show(Material $material): JsonResponse
    {
        return $this->ok(new MaterialResource($material));
    }

    public function store(MaterialRequest $request): JsonResponse
    {
        $material = Material::create($request->validated());

        return $this->criado(new MaterialResource($material), 'Material criado com sucesso.');
    }

    public function update(MaterialRequest $request, Material $material): JsonResponse
    {
        $material->update($request->validated());

        return $this->ok(new MaterialResource($material), 'Material actualizado com sucesso.');
    }

    public function destroy(Material $material): JsonResponse
    {
        $material->update(['activo' => false]);

        return $this->ok(null, 'Material desactivado com sucesso.');
    }

    /**
     * Declara o stock inicial de um material (o que ja existia em armazem
     * antes do sistema). Da entrada via StockService, que calcula o custo
     * medio — a base para o lucro das primeiras vendas ser correcto.
     */
    public function stockInicial(StockInicialRequest $request, Material $material, StockService $stock): JsonResponse
    {
        $dados = $request->validated();

        $stock->entrada(
            material: $material,
            quantidadeKg: (float) $dados['quantidade_kg'],
            custoKg: (float) $dados['custo_kg'],
            origemTipo: 'StockInicial',
            origemId: null,
            userId: $request->user()->id,
            data: $dados['data'] ?? now()->toDateString(),
            observacoes: 'Stock inicial declarado',
        );

        return $this->ok(
            new MaterialResource($material->fresh()),
            'Stock inicial registado com sucesso.'
        );
    }
}