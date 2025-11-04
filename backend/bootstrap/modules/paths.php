<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;

return function (Application $application): void {
    // Establecer explícitamente las rutas clave
    $application->useDatabasePath(base_path('../database'));
    $application->usePublicPath(base_path('public'));
};
