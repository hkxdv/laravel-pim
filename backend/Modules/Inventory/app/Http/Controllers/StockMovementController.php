<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StaffUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\App\Http\Requests\StoreInventoryStockMovementRequest;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Models\StockMovement;

final class StockMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $pidRaw = $request->query('product_id', 0);
        $productId = is_numeric($pidRaw)
            ? (int) $pidRaw : 0;
        $perRaw = $request->query('per_page', 15);
        $perPage = is_numeric($perRaw)
            ? (int) $perRaw : 15;

        $query = StockMovement::query()->with('product');
        if ($productId > 0) {
            $query->where('product_id', $productId);
        }

        $paginator = $query->latest('performed_at')
            ->paginate($perPage)
            ->appends($request->query());

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

    public function store(
        StoreInventoryStockMovementRequest $request
    ): JsonResponse {
        /** @var StaffUsers $user */
        $user = $this->requireStaffUser($request);

        /** @var array{product_id:int,type:string,quantity?:int,new_stock?:int,notes?:string} $data */
        $data = $request->validated();

        $result = DB::transaction(
            function () use ($data, $user, $request): array {
                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($data['product_id']);

                $type = $data['type'];
                $quantity = (int) ($data['quantity'] ?? 0);
                $newStock = $data['new_stock'] ?? null;

                if ($type === 'in') {
                    $product->stock += $quantity;
                } elseif ($type === 'out') {
                    throw_if(
                        $product->stock < $quantity,
                        ValidationException::withMessages([
                            'quantity' => [
                                'Stock insuficiente para realizar la salida.',
                            ],
                        ])
                    );

                    $product->stock -= $quantity;
                } elseif ($type === 'adjust') {
                    $product->stock = (int) $newStock;
                }

                $product->save();

                $uidRaw = $user->getAuthIdentifier();
                $uid = is_numeric($uidRaw) ? (int) $uidRaw : 0;

                $movement = new StockMovement([
                    'product_id' => $product->id,
                    'user_id' => $uid,
                    'type' => $type,
                    'quantity' => $quantity,
                    'new_stock' => $newStock,
                    'notes' => $data['notes'] ?? null,
                    'performed_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) (
                        $request->header('User-Agent') ?? ''
                    ),
                ]);
                $movement->save();

                return [$product, $movement];
            }
        );

        /** @var array{0:Product,1:StockMovement} $result */
        [$updatedProduct, $movement] = $result;

        return response()->json([
            'product' => $updatedProduct,
            'movement' => $movement,
        ], 201);
    }
}
