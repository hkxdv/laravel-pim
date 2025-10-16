<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Http\JsonResponse;

/**
 * Interfaz para el formateo de respuestas API.
 * Define cómo se formatean las respuestas JSON para las APIs.
 */
interface ApiResponseFormatterInterface
{
    /**
     * Genera una respuesta JSON exitosa.
     *
     * @param  mixed  $data  Los datos a incluir en la respuesta
     * @param  string|null  $message  Mensaje opcional
     * @param  int  $statusCode  Código HTTP de estado
     * @param  array<string, mixed>  $meta  Metadatos adicionales
     * @return JsonResponse Respuesta JSON
     */
    public function successResponse(
        $data = null,
        ?string $message = null,
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse;

    /**
     * Genera una respuesta JSON de error.
     *
     * @param  string  $message  Mensaje de error
     * @param  int  $statusCode  Código HTTP de estado
     * @param  array<string, mixed>  $errors  Errores detallados
     * @return JsonResponse Respuesta JSON
     */
    public function errorResponse(
        string $message,
        int $statusCode,
        array $errors = []
    ): JsonResponse;

    /**
     * Genera una respuesta JSON con paginación.
     *
     * @param  mixed  $data  Datos paginados
     * @param  array<string, mixed>  $paginationInfo  Información de paginación
     * @param  string|null  $message  Mensaje opcional
     * @param  int  $statusCode  Código HTTP de estado
     * @return JsonResponse Respuesta JSON
     */
    public function paginatedResponse(
        $data,
        array $paginationInfo,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse;
}
