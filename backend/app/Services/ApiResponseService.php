<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ApiResponseFormatterInterface;
use App\Traits\ApiResponseFormatter;

/**
 * Servicio para la creación de respuestas de API estandarizadas.
 *
 * Esta clase implementa la interfaz ApiResponseFormatterInterface y utiliza el trait
 * ApiResponseFormatter para proporcionar una implementación concreta y reutilizable
 * para generar respuestas JSON consistentes en toda la aplicación.
 */
final class ApiResponseService implements ApiResponseFormatterInterface
{
    use ApiResponseFormatter {
        successResponse as public successResponse;
        errorResponse as public errorResponse;
        paginatedResponse as public paginatedResponse;
    }
}
