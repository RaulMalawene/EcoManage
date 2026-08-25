<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\MaterialRequest;
use App\Http\Requests\QuebraRequest;
use App\Http\Requests\StockInicialRequest;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\MovimentoStockResource;
use App\Models\Material;
use App\Models\MovimentoStock;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Regista uma quebra de stock (humidade, danos, manuseamento, etc.):
     * kg que saem do armazem sem terem sido vendidos. Reduz o stock do
     * material e soma ao acumulado de quebras — nao entra como receita
     * nos relatorios.
     */
    public function quebra(QuebraRequest $request, Material $material, StockService $stock): JsonResponse
    {
        $dados = $request->validated();

        $movimento = $stock->quebra(
            material: $material,
            quantidadeKg: (float) $dados['quantidade_kg'],
            userId: $request->user()->id,
            data: $dados['data'] ?? now()->toDateString(),
            observacoes: $dados['motivo'] ?? null,
        );

        return $this->ok([
            'material' => new MaterialResource($material->fresh()),
            'movimento' => [
                'id' => $movimento->id,
                'quantidade_kg' => (float) $movimento->quantidade_kg,
                'stock_apos_kg' => (float) $movimento->stock_apos_kg,
                'data' => $movimento->data->toDateString(),
            ],
        ], 'Quebra registada com sucesso.');
    }

    /**
     * Historico de quebras deste material (relatorio): so leitura,
     * cada linha e' um movimento de stock com origem "Quebra". Aceita
     * filtro por periodo, tal como o extracto do caixa.
     */
    public function quebras(Request $request, Material $material): JsonResponse
    {
        $query = MovimentoStock::with('user')
            ->doMaterial($material->id)
            ->where('origem_tipo', 'Quebra')
            ->cronologico();

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data', [
                $request->date('data_inicio'),
                $request->date('data_fim'),
            ]);
        }

        // Totais do periodo filtrado (nao do historico completo).
        $totais = (clone $query)->get();
        $quantidadeTotalKg = (float) $totais->sum('quantidade_kg');
        $valorTotal = $totais->sum(fn ($m) => (float) $m->quantidade_kg * (float) $m->custo_kg);

        $movimentos = $query->paginate(30);

        return $this->ok([
            'material' => [
                'id' => $material->id,
                'nome' => $material->nome,
                'total_quebras_kg' => (float) $material->total_quebras_kg,
            ],
            'itens' => MovimentoStockResource::collection($movimentos),
            'resumo_periodo' => [
                'quantidade_total_kg' => round($quantidadeTotalKg, 3),
                'valor_total' => round($valorTotal, 2),
            ],
            'paginacao' => [
                'total' => $movimentos->total(),
                'pagina' => $movimentos->currentPage(),
                'por_pagina' => $movimentos->perPage(),
                'ultima_pagina' => $movimentos->lastPage(),
            ],
        ]);
    }

    /**
     * Exporta os materiais e o stock em PDF. Mesma construcao de query e
     * o mesmo resumo do index() — que ja nao e' paginado, so troca o
     * JSON por um PDF.
     */
    public function exportar(Request $request): Response
    {
        $query = Material::query();

        if (! $request->boolean('incluir_inactivos')) {
            $query->where('activo', true);
        }

        if ($request->filled('pesquisa')) {
            $termo = $request->string('pesquisa');
            $query->where('nome', 'like', "%{$termo}%");
        }

        if ($request->boolean('em_alerta')) {
            $query->whereNotNull('limite_alerta_kg')
                ->whereColumn('stock_kg', '>=', 'limite_alerta_kg');
        }

        $materiais = $query->orderBy('nome')->get();

        $valorStockTotal = $materiais->sum(
            fn ($m) => (float) $m->stock_kg * (float) $m->custo_medio_kg
        );

        return Pdf::loadView('pdf.materiais', [
            'materiais' => $materiais,
            'resumo' => [
                'total_materiais' => $materiais->count(),
                'valor_stock_total' => round($valorStockTotal, 2),
            ],
        ])->download('materiais-' . now()->format('Y-m-d') . '.pdf');
    }
}