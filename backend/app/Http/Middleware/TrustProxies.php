<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

final class TrustProxies extends Middleware
{
    /**
     * Los proxies de confianza para esta aplicación.
     *
     * @var array<int, string>|string|null
     */
    // NOTA DE SEGURIDAD: En producción, es muy recomendable reemplazar '*'
    // con las direcciones IP de los proxies de confianza para evitar vulnerabilidades.
    protected $proxies = '*';

    /**
     * Las cabeceras que deben usarse para detectar proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
