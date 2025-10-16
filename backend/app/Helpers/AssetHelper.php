<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper para gestionar activos protegidos
 */
final class AssetHelper
{
    /**
     * Genera una URL para un activo protegido
     *
     * @param  string  $path  Ruta del archivo relativa a la carpeta de activos protegidos
     * @return string URL completa al activo
     */
    public static function protectedAsset(string $path): string
    {
        return url('/assets/'.mb_ltrim($path, '/'));
    }

    /**
     * Mueve un archivo a la carpeta de activos protegidos
     *
     * @param  string  $sourcePath  Ruta completa del archivo fuente
     * @param  string  $targetPath  Ruta dentro de la carpeta de activos protegidos
     * @return bool Éxito o fracaso de la operación
     */
    public static function moveToProtectedAssets(
        string $sourcePath,
        string $targetPath
    ): bool {
        // Asegurarse de que la carpeta de destino existe
        $targetDir = storage_path(
            'app/protected/assets/'.dirname($targetPath)
        );
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Mover el archivo
        $fullTargetPath = storage_path(
            'app/protected/assets/'.$targetPath
        );

        return rename($sourcePath, $fullTargetPath);
    }

    /**
     * Copia un archivo a la carpeta de activos protegidos
     *
     * @param  string  $sourcePath  Ruta completa del archivo fuente
     * @param  string  $targetPath  Ruta dentro de la carpeta de activos protegidos
     * @return bool Éxito o fracaso de la operación
     */
    public static function copyToProtectedAssets(
        string $sourcePath,
        string $targetPath
    ): bool {
        // Asegurarse de que la carpeta de destino existe
        $targetDir = storage_path(
            'app/protected/assets/'.dirname($targetPath)
        );
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Copiar el archivo
        $fullTargetPath = storage_path(
            'app/protected/assets/'.$targetPath
        );

        return copy($sourcePath, $fullTargetPath);
    }
}
