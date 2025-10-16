<?php

declare(strict_types=1);

use App\Http\Middleware\ProtectStaticAssets;
use Illuminate\Support\Facades\Route;

// Rutas para activos protegidos
Route::middleware([ProtectStaticAssets::class])->group(function (): void {
    // Esta ruta servirá los archivos de una carpeta protegida y hará fallback a storage público si aplica
    Route::get('/assets/{path}', function (string $path) {
        $protectedPath = storage_path('app/protected/assets/'.$path);
        $publicPath = storage_path('app/public/'.$path);

        $filePath = null;
        if (file_exists($protectedPath)) {
            $filePath = $protectedPath;
        } elseif (file_exists($publicPath)) {
            $filePath = $publicPath;
        }

        abort_unless(is_string($filePath), 404);

        // Determinar el tipo MIME basado en la extensión
        $extension = mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        $detected = $mimeTypes[$extension]
            ?? (function_exists('mime_content_type')
                ? @mime_content_type($filePath)
                : null
            );
        $contentType = $detected ?: 'application/octet-stream';

        return response()->file($filePath, ['Content-Type' => $contentType]);
    })->where('path', '.*');
});
