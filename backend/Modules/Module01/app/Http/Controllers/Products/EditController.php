<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers\Products;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Response as InertiaResponse;
use Modules\Module01\App\Http\Controllers\Module01BaseController;
use Modules\Module01\App\Http\Requests\UpdateProductRequest;

/**
 * Controlador para la edición de productos.
 */
final class EditController extends Module01BaseController
{
    /**
     * Muestra el formulario de edición de un producto existente.
     */
    public function show(Request $request, int $id): InertiaResponse
    {
        $product = $this->inventoryManager->getProductById($id);
        abort_unless($product, 404, 'Producto no encontrado');

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
    public function update(UpdateProductRequest $request, int $id): RedirectResponse
    {
        try {
            $product = $this->inventoryManager->getProductById($id);
            if (! $product instanceof \App\Models\Product) {
                return to_route('internal.module01.products.index')
                    ->with('error', 'Producto no encontrado. No se pudo realizar la actualización.');
            }

            $validatedData = $request->validated();
            $updated = $this->inventoryManager->updateProduct($id, $validatedData);

            return to_route('internal.module01.products.index')
                ->with('success', sprintf("Producto '%s' actualizado exitosamente.", $updated?->name));
        } catch (Exception $exception) {
            Log::error('Error al actualizar producto: '.$exception->getMessage(), [
                'product_id' => $id,
                'data' => $request->all(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return back()
                ->withInput($request->all())
                ->with('error', 'Ocurrió un error al actualizar el producto. Por favor, inténtalo nuevamente.');
        }
    }

    /**
     * Elimina un producto existente.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $product = $this->inventoryManager->getProductById($id);
            if (! $product instanceof \App\Models\Product) {
                return to_route('internal.module01.products.index')
                    ->with('error', 'Producto no encontrado. No se pudo realizar la eliminación.');
            }

            $deleted = $this->inventoryManager->deleteProduct($id);

            if ($deleted) {
                return to_route('internal.module01.products.index')
                    ->with('success', sprintf("Producto '%s' eliminado exitosamente.", $product->name));
            }

            return to_route('internal.module01.products.index')
                ->with('error', 'No se pudo eliminar el producto. Intente nuevamente.');
        } catch (Exception $exception) {
            Log::error('Error al eliminar producto: '.$exception->getMessage(), [
                'product_id' => $id,
                'trace' => $exception->getTraceAsString(),
            ]);

            return to_route('internal.module01.products.index')
                ->with('error', 'Ocurrió un error al eliminar el producto. Por favor, inténtalo nuevamente.');
        }
    }
}
