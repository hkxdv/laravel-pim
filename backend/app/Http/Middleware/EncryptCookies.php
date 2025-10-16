<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

final class EncryptCookies extends Middleware
{
    /**
     * Los nombres de las cookies que no deben ser encriptadas.
     *
     * @var array<int, string>
     */
    protected $except = [
        'appearance',
        'sidebar_state',
    ];
}
