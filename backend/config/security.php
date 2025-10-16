<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad del Sistema Dual
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene todas las configuraciones de seguridad específicas
    | para el sistema dual de autenticación (staff y ciudadanos).
    |
    */

    'authentication' => [
        /*
        |--------------------------------------------------------------------------
        | Configuración de Rate Limiting
        |--------------------------------------------------------------------------
        */
        'rate_limiting' => [
            'staff' => [
                'max_attempts' => env('STAFF_MAX_LOGIN_ATTEMPTS', 3),
                'lockout_duration' => env('STAFF_LOCKOUT_DURATION', 900),
                'throttle_window' => env('STAFF_THROTTLE_WINDOW', 300),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Configuración de Sesiones
        |--------------------------------------------------------------------------
        */
        'sessions' => [
            'staff' => [
                'timeout' => env('STAFF_SESSION_TIMEOUT', 7200),
                'remember_me' => env('STAFF_REMEMBER_ME', false),
                'concurrent_sessions' => env('STAFF_CONCURRENT_SESSIONS', 1),
                'force_logout_on_ip_change' => env('STAFF_FORCE_LOGOUT_IP_CHANGE', true),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Configuración de Contraseñas
        |--------------------------------------------------------------------------
        */
        'passwords' => [
            'staff' => [
                'min_length' => env('STAFF_PASSWORD_MIN_LENGTH', 12),
                'require_uppercase' => env('STAFF_PASSWORD_REQUIRE_UPPERCASE', true),
                'require_lowercase' => env('STAFF_PASSWORD_REQUIRE_LOWERCASE', true),
                'require_numbers' => env('STAFF_PASSWORD_REQUIRE_NUMBERS', true),
                'require_symbols' => env('STAFF_PASSWORD_REQUIRE_SYMBOLS', true),
                'max_age_days' => env('STAFF_PASSWORD_MAX_AGE_DAYS', 90),
                'history_count' => env('STAFF_PASSWORD_HISTORY_COUNT', 12),
                'reset_token_expiry' => env('STAFF_PASSWORD_RESET_EXPIRY', 15),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Dos Factores (2FA)
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        'staff' => [
            'required' => env('STAFF_2FA_REQUIRED', true),
            'methods' => ['totp', 'sms', 'email'],
            'backup_codes_count' => 10,
            'totp_window' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Auditoría y Logging
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        'log_failed_logins' => env('LOG_FAILED_LOGINS', true),
        'log_successful_logins' => env('LOG_SUCCESSFUL_LOGINS', true),
        'log_password_changes' => env('LOG_PASSWORD_CHANGES', true),
        'log_session_events' => env('LOG_SESSION_EVENTS', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 365),

        'events' => [
            'login_success',
            'login_failed',
            'logout',
            'password_changed',
            'password_reset_requested',
            'password_reset_completed',
            'account_locked',
            'account_unlocked',
            'session_expired',
            'suspicious_activity',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad de Headers HTTP
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'force_https' => env('FORCE_HTTPS', true),
        'hsts_max_age' => env('HSTS_MAX_AGE', 31536000), // 1 año
        'content_security_policy' => env('CSP_ENABLED', true),
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Detección de Amenazas
    |--------------------------------------------------------------------------
    */
    'threat_detection' => [
        'enabled' => env('THREAT_DETECTION_ENABLED', true),

        'suspicious_patterns' => [
            'multiple_failed_logins' => [
                'threshold' => 5,
                'window_minutes' => 10,
            ],
            'rapid_requests' => [
                'threshold' => 100,
                'window_minutes' => 1,
            ],
            'unusual_user_agent' => true,
            'tor_exit_nodes' => env('BLOCK_TOR_NODES', false),
        ],

        'actions' => [
            'log' => true,
            'notify_admin' => env('NOTIFY_ADMIN_THREATS', true),
            'temporary_block' => env('TEMPORARY_BLOCK_THREATS', true),
            'block_duration_minutes' => env('THREAT_BLOCK_DURATION', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Cookies
    |--------------------------------------------------------------------------
    */
    'cookies' => [
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'same_site' => env('SESSION_SAME_SITE', 'lax'),
        'encrypt' => env('SESSION_ENCRYPT', true),
        'domain' => env('SESSION_DOMAIN', null),
        'path' => env('SESSION_PATH', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Validación de Entrada
    |--------------------------------------------------------------------------
    */
    'input_validation' => [
        'max_request_size' => env('MAX_REQUEST_SIZE', '10M'),
        'max_file_uploads' => env('MAX_FILE_UPLOADS', 20),
        'allowed_file_types' => [
            'documents' => ['pdf', 'doc', 'docx', 'txt'],
            'images' => ['jpg', 'jpeg', 'png', 'gif'],
            'archives' => ['zip', 'rar'],
        ],
        'sanitize_input' => env('SANITIZE_INPUT', true),
        'strip_tags' => env('STRIP_TAGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Backup y Recuperación
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('SECURITY_BACKUP_ENABLED', true),
        'frequency' => env('BACKUP_FREQUENCY', 'daily'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'encrypt_backups' => env('ENCRYPT_BACKUPS', true),
        'verify_integrity' => env('VERIFY_BACKUP_INTEGRITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración para seguridad de archivos multimedia
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'base_directory' => env('MEDIA_PROFILE_BASE', 'profile-images'),
        'signed_urls' => [
            'enabled' => env('MEDIA_SIGNED_URLS', true),
            'expiration_minutes' => env('MEDIA_SIGNED_URLS_TTL', 60),
        ],
        'check_referrer' => env('MEDIA_CHECK_REFERRER', false),
        'allowed_referrer_hosts' => array_filter(
            explode(',', (string) env(
                'MEDIA_ALLOWED_REFERRERS',
                'localhost,127.0.0.1'
            ))
        ),
        'cache' => [
            'visibility' => env('MEDIA_CACHE_VISIBILITY', 'private'),
            'max_age' => env('MEDIA_CACHE_MAX_AGE', 3600),
        ],
    ],

];
