<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tighten\Ziggy\Ziggy;

// Ruta de diagnóstico para verificar Ziggy

if (app()->environment('local')) {
    Route::get(
        '/ziggy-debug',
        function () {
            $groups = [
                'public' => new Ziggy(group: 'public'),
                'staff' => new Ziggy(group: 'staff'),
            ];

            // Asegurar tipos estrictos para evitar 'mixed' en PHPStan
            $publicArray = $groups['public']->toArray();
            $staffArray = $groups['staff']->toArray();

            $publicRoutes = is_array($publicArray['routes'] ?? null)
                ? $publicArray['routes']
                : [];
            $staffRoutes = is_array($staffArray['routes'] ?? null)
                ? $staffArray['routes']
                : [];

            return response()->json([
                'groups' => [
                    'public' => count($publicRoutes),
                    'staff' => count($staffRoutes),
                ],
                'routes' => [
                    'public' => array_keys($publicRoutes),
                    'staff' => array_keys($staffRoutes),
                ],
            ]);
        }
    )->name('ziggy-debug');
}
