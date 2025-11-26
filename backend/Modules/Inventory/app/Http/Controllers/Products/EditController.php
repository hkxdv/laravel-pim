<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers\Products;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Response as InertiaResponse;
use Modules\Inventory\App\Http\Controllers\InventoryBaseController;
use Modules\Inventory\App\Http\Requests\UpdateInventoryProductRequest;
use Modules\Inventory\App\Models\Product;

/**
 * Controlador para la edición de productos.
 */
final class EditController extends InventoryBaseController
{
    /**
     * Muestra el formulario de edición de un producto existente.
     */
    public function show(Request $request, int $id): InertiaResponse
    {
        $product = $this->inventoryManager->getProductById($id);
        abort_unless(
            $product instanceof Product,
            404,
            'Producto no encontrado'
        );

        $additionalData = [
            'product' => $product,
        ];

        return $this->prepareAndRenderModuleView(
            view: 'product/edit',
            request: $request,
            additionalData: $additionalData
        );
    }

    /**
     * Actualiza un producto existente.
     */
    public function update(
        UpdateInventoryProductRequest $request,
        int $id
    ): RedirectResponse {
        try {
            $product = $this->inventoryManager->getProductById($id);
            if (! $product instanceof Product) {
                return to_route('internal.inventory.products.index')
                    ->with(
                        'error',
                        'Producto no encontrado. No se pudo realizar la actualización.'
                    );
            }

            $validatedData = $request->validated();
            $updated = $this->inventoryManager->updateProduct($id, $validatedData);

            $nameRaw = $updated?->getAttribute('name');
            $name = is_string($nameRaw) ? $nameRaw : '';

            return to_route('internal.inventory.products.index')
                ->with(
                    'success',
                    sprintf("Producto '%s' actualizado exitosamente.", $name)
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al actualizar producto: '.$exception->getMessage(),
                [
                    'product_id' => $id,
                    'data' => $request->all(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return back()
                ->withInput($request->all())
                ->with(
                    'error',
                    'Ocurrió un error al actualizar el producto. Por favor, inténtalo nuevamente.'
                );
        }
    }

    /**
     * Elimina un producto existente.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $product = $this->inventoryManager->getProductById($id);
            if (! $product instanceof Product) {
                return to_route('internal.inventory.products.index')
                    ->with(
                        'error',
                        'Producto no encontrado. No se pudo realizar la eliminación.'
                    );
            }

            $deleted = $this->inventoryManager->deleteProduct($id);

            if ($deleted) {
                $nameRaw = $product->getAttribute('name');
                $name = is_string($nameRaw) ? $nameRaw : '';

                return to_route('internal.inventory.products.index')
                    ->with(
                        'success',
                        sprintf("Producto '%s' eliminado exitosamente.", $name)
                    );
            }

            return to_route('internal.inventory.products.index')
                ->with(
                    'error',
                    'No se pudo eliminar el producto. Intente nuevamente.'
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al eliminar producto: '.$exception->getMessage(),
                [
                    'product_id' => $id,
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return to_route('internal.inventory.products.index')
                ->with(
                    'error',
                    'Ocurrió un error al eliminar el producto. Por favor, inténtalo nuevamente.'
                );
        }
    }
}
