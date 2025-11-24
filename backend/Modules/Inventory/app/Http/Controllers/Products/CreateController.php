<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers\Products;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Response as InertiaResponse;
use Modules\Inventory\App\Http\Controllers\InventoryBaseController;
use Modules\Inventory\App\Http\Requests\StoreInventoryProductRequest;

/**
 * Controlador para la creación de productos.
 */
final class CreateController extends InventoryBaseController
{
    /**
     * Muestra el formulario de creación de un nuevo producto.
     */
    public function show(Request $request): InertiaResponse
    {
        $additionalData = [];

        return $this->prepareAndRenderModuleView(
            view: 'product/create',
            request: $request,
            additionalData: $additionalData
        );
    }

    /**
     * Almacena un nuevo producto.
     */
    public function store(
        StoreInventoryProductRequest $request
    ): RedirectResponse|InertiaResponse {
        try {
            $validatedData = $request->validated();
            $product = $this->inventoryManager->createProduct($validatedData);

            if (is_string($request->header('X-Inertia'))) {
                session()->flash(
                    'success',
                    sprintf(
                        "Producto '%s' creado exitosamente.",
                        is_string($product->getAttribute('name'))
                            ? $product->getAttribute('name') : ''
                    )
                );

                $additionalData = [
                    'product' => $product,
                    'preventRedirect' => true,
                ];

                return $this->prepareAndRenderModuleView(
                    view: 'product/create',
                    request: $request,
                    additionalData: $additionalData
                );
            }

            return to_route('internal.inventory.products.index')
                ->with(
                    'success',
                    sprintf(
                        "Producto '%s' creado exitosamente.",
                        is_string($product->getAttribute('name'))
                            ? $product->getAttribute('name') : ''
                    )
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al crear producto: '.$exception->getMessage(),
                [
                    'data' => $request->all(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            if (is_string($request->header('X-Inertia'))) {
                session()->flash(
                    'error',
                    'Ocurrió un error al crear el producto. Por favor, inténtalo nuevamente.'
                );

                $additionalData = [
                    'errors' => [
                        'general' => 'Ocurrió un error al crear el producto. Por favor, inténtalo nuevamente.',
                    ],
                ];

                return $this->prepareAndRenderModuleView(
                    view: 'product/create',
                    request: $request,
                    additionalData: $additionalData
                );
            }

            return back()
                ->withInput($request->all())
                ->with(
                    'error',
                    'Ocurrió un error al crear el producto. Por favor, inténtalo nuevamente.'
                );
        }
    }
}
