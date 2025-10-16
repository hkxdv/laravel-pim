<?php

declare(strict_types=1);

// Configuración para seeders del sistema.
// Centraliza variables de entorno usadas para crear usuarios base (guard: staff).

$max = (int) env('USER_STAFF_MAX', 10);
$max = $max > 0 ? min($max, 50) : 10;

$staff = [];
for ($i = 1; $i <= $max; $i++) {
    $staff[] = [
        'email' => env("USER_STAFF_{$i}_EMAIL"),
        'password' => env("USER_STAFF_{$i}_PASSWORD"),
        'name' => env("USER_STAFF_{$i}_NAME", "Usuario {$i}"),
        'role' => env("USER_STAFF_{$i}_ROLE"),
        'force_password_update' => filter_var(env("USER_STAFF_{$i}_FORCE_PASSWORD_UPDATE", false), FILTER_VALIDATE_BOOL),
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
