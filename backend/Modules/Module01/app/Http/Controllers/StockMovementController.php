<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StaffUsers;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Module01\App\Http\Requests\StoreStockMovementRequest;

final class StockMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $productId = (int) $request->query('product_id', 0);
        $perPage = (int) $request->query('per_page', 15);

        $query = StockMovement::query()->with('product');
        if ($productId > 0) {
            $query->where('product_id', $productId);
        }

        $paginator = $query->latest('performed_at')->paginate($perPage)->appends($request->query());

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

    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        /** @var StaffUsers $user */
        $user = $this->requireStaffUser($request);

        /** @var array{product_id:int,type:string,quantity?:int,new_stock?:int,notes?:string} $data */
        $data = $request->validated();

        $result = DB::transaction(function () use ($data, $user, $request): array {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);

            $type = $data['type'];
            $quantity = (int) ($data['quantity'] ?? 0);
            $newStock = $data['new_stock'] ?? null;

            if ($type === 'in') {
                $product->stock += $quantity;
            } elseif ($type === 'out') {
                throw_if($product->stock < $quantity, ValidationException::withMessages([
                    'quantity' => ['Stock insuficiente para realizar la salida.'],
                ]));

                $product->stock -= $quantity;
            } elseif ($type === 'adjust') {
                $product->stock = (int) $newStock;
            }

            $product->save();

            $movement = new StockMovement([
                'product_id' => $product->id,
                'user_id' => (int) $user->getAuthIdentifier(),
                'type' => $type,
                'quantity' => $quantity,
                'new_stock' => $newStock,
                'notes' => $data['notes'] ?? null,
                'performed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) ($request->header('User-Agent') ?? ''),
            ]);
            $movement->save();

            return [$product, $movement];
        });

        /** @var array{0:Product,1:StockMovement} $result */
        [$updatedProduct, $movement] = $result;

        return response()->json([
            'product' => $updatedProduct,
            'movement' => $movement,
        ], 201);
    }
}
