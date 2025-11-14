<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers\StockMovements;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Response as InertiaResponse;
use Modules\Module01\App\Http\Controllers\Module01BaseController;
use Modules\Module01\App\Http\Requests\StoreStockMovementRequest;

/**
 * Controlador para la creación de movimientos de stock (vista y almacenamiento).
 */
final class CreateController extends Module01BaseController
{
    /**
     * Muestra el formulario de creación de un nuevo movimiento de stock.
     */
    public function show(Request $request): InertiaResponse
    {
        return $this->prepareAndRenderModuleView(
            view: 'stock-movement/create',
            request: $request,
            additionalData: []
        );
    }

    /**
     * Almacena un nuevo movimiento de stock y actualiza el stock del producto.
     */
    public function store(StoreStockMovementRequest $request)
    {
        /** @var \App\Models\StaffUsers|null $user */
        $user = $this->getAuthenticatedUser();
        abort_if($user === null, 403, 'Usuario no autenticado');

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

        // Flash de éxito y re-render del formulario con datos mínimos
        if ($request->header('X-Inertia')) {
            session()->flash(
                'success',
                'Movimiento de stock registrado exitosamente.'
            );

            $additionalData = [
                'movement' => $movement,
                'product' => $updatedProduct,
            ];

            return $this->prepareAndRenderModuleView(
                view: 'stock-movement/create',
                request: $request,
                additionalData: $additionalData
            );
        }

        return to_route('internal.module01.stock_movements.index')
            ->with('success', 'Movimiento de stock registrado exitosamente.');
    }
}
