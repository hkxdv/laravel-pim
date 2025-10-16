<?php

declare(strict_types=1);

use App\Helpers\AssetHelper;

if (! function_exists('protected_asset')) {
    /**
     * Genera una URL para un activo protegido
     *
     * @param  string  $path  Ruta del archivo relativa a la carpeta de activos protegidos
     * @return string URL completa al activo
     */
    function protected_asset(string $path): string
    {
        return AssetHelper::protectedAsset($path);
    }
}
