<?php

declare(strict_types=1);

// Configuración para seeders del sistema.
// Centraliza variables de entorno usadas para crear usuarios base (guard: staff).

$max = (int) env('USER_STAFF_MAX', 10);
$max = $max > 0 ? min($max, 50) : 10;

$staff = [];
for ($i = 1; $i <= $max; $i++) {
    $staff[] = [
        'email' => env(sprintf('USER_STAFF_%d_EMAIL', $i)),
        'password' => env(sprintf('USER_STAFF_%d_PASSWORD', $i)),
        'name' => env(sprintf('USER_STAFF_%d_NAME', $i), 'Usuario '.$i),
        'role' => env(sprintf('USER_STAFF_%d_ROLE', $i)),
        'force_password_update' => filter_var(env(sprintf('USER_STAFF_%d_FORCE_PASSWORD_UPDATE', $i), false), FILTER_VALIDATE_BOOL),
    ];
}

return [
    'users' => [
        'staff' => [
            'max' => $max,
            'list' => $staff,
        ],
    ],
];
