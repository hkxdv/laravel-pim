<?php

declare(strict_types=1);

return [
    'env' => [
        'preferred' => '.env.local',
        'testing' => '.env.testing',
        'docker' => '.env.docker',
        'production' => '.env.production.local',
        'fallback_pg' => '.env.pg.local',
        'users_file' => '.env.users',
        'required' => ['APP_ENV', 'APP_KEY'],
    ],
];
