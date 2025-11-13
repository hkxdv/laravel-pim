<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Module01\App\Http\Requests\StoreProductRequest;
use Modules\Module01\App\Http\Requests\UpdateProductRequest;
use Modules\Module01\App\Services\Search\ProductSearchResolver;

final class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $search = (string) $request->query('search', '');
        $perPage = (int) $request->query('per_page', 15);
        $sortBy = (string) $request->query('sort_by', 'name');
        $sortDir = mb_strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Product::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('sku', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('brand', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('model', 'like', sprintf('%%%s%%', $search));
            });
        }

        $allowedSorts = ['name', 'sku', 'brand', 'model', 'stock', 'price', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'name';
        }

        $paginator = $query->orderBy($sortBy, $sortDir)->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $q = (string) $request->query('q', (string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 15);
        $sortField = (string) $request->query('sort_field', (string) $request->query('sort_by', 'created_at'));
        $sortDir = mb_strtolower((string) $request->query('sort_dir', (string) $request->query('sort_direction', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $params = [
            'q' => $q,
            'is_active' => $request->has('is_active') ? (bool) $request->boolean('is_active') : null,
            'brand' => (string) $request->query('brand', ''),
            'model' => (string) $request->query('model', ''),
            'sort_field' => $sortField,
            'sort_direction' => $sortDir,
            'per_page' => $perPage,
        ];

        $mode = ProductSearchResolver::currentMode();
        $engine = ProductSearchResolver::resolve();

        Log::info('Module01 products.search', [
            'mode' => $mode,
            'engine' => $engine::class,
            'params' => $params,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'req_header_x_search_mode' => $request->headers->get('X-Search-Mode'),
        ]);

        $paginator = $engine->search($params, $perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'q' => $q,
                'mode' => $mode,
            ],
        ])->header('X-Search-Mode', $mode);
    }

    public function show(Request $request, int $product): JsonResponse
    {
        $this->requireStaffUser($request);

        $model = Product::query()->findOrFail($product);

        return response()->json($model);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->requireStaffUser($request);

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $product = DB::transaction(fn () => Product::query()->create($data));

        return response()->json($product, 201);
    }

    public function update(UpdateProductRequest $request, int $product): JsonResponse
    {
        $this->requireStaffUser($request);

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $model = Product::query()->findOrFail($product);
        DB::transaction(function () use ($model, $data): void {
            $model->fill($data);
            $model->save();
        });

        return response()->json($model);
    }

    public function destroy(Request $request, int $product): JsonResponse
    {
        $this->requireStaffUser($request);

        $model = Product::query()->findOrFail($product);
        $model->delete();

        return response()->json(['deleted' => true]);
    }
}
