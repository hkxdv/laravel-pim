<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Module01\App\Http\Controllers\Products\CreateController;
use Modules\Module01\App\Http\Controllers\Products\EditController;
use Modules\Module01\App\Http\Controllers\Products\ListController;
use Modules\Module01\App\Http\Controllers\Products\SuggestController;

/**
 * Grupo de rutas para la gestión de productos (CRUD de vistas).
 * Prefijo de URL: '/internal/module-01/products'
 * Prefijo de Nombre: 'internal.module01.products.'
 */
Route::prefix('products')->name('products.')->group(function (): void {
    // Lista de productos
    Route::get(
        '/',
        ListController::class
    )->name('index');

    // Endpoint de sugerencias para autocompletado
    Route::get(
        '/suggest',
        SuggestController::class
    )->name('suggest');

    // Formulario de creación
    Route::get(
        '/create',
        [CreateController::class, 'show']
    )->name('create');

    // Almacena nuevo producto
    Route::post(
        '/',
        [CreateController::class, 'store']
    )->name('store');

    // Formulario de edición
    Route::get(
        '/{product}/edit',
        [EditController::class, 'show']
    )->name('edit');

    // Actualiza producto
    Route::put(
        '/{product}',
        [EditController::class, 'update']
    )->name('update');

    // Elimina producto
    Route::delete(
        '/{product}',
        [EditController::class, 'destroy']
    )->name('destroy');
});
