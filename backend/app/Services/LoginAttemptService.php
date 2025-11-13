<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class LoginAttemptService
{
    /**
     * La cantidad máxima de intentos fallidos permitidos.
     */
    private int $maxAttempts = 5;

    /**
     * El período de bloqueo inicial en minutos.
     */
    private int $initialLockoutPeriod = 1;

    /**
     * El factor de multiplicación del período de bloqueo.
     */
    private int $lockoutMultiplier = 2;

    /**
     * El período máximo de bloqueo en minutos.
     */
    private int $maxLockoutPeriod = 60;

    /**
     * Verifica si el usuario o IP ha excedido el límite de intentos fallidos.
     */
    public function hasTooManyAttempts(string $identifier, string $ip): bool
    {
        $key = $this->getAttemptKey($identifier, $ip);

        return RateLimiter::tooManyAttempts($key, $this->maxAttempts);
    }

    /**
     * Incrementa el contador de intentos fallidos.
     */
    public function incrementAttempts(string $identifier, string $ip): void
    {
        $key = $this->getAttemptKey($identifier, $ip);
        RateLimiter::hit(
            $key,
            $this->calculateDecayMinutes($identifier, $ip) * 60
        );

        // Registrar el intento fallido para el análisis de seguridad
        Log::warning('Intento de inicio de sesión fallido', [
            'identifier' => $identifier,
            'ip' => $ip,
            'attempts' => $this->getAttempts($identifier, $ip),
        ]);

        // Si el número de intentos es múltiplo de 3, verificar si es un ataque potencial
        $attempts = $this->getAttempts($identifier, $ip);
        if ($attempts > 0 && $attempts % 3 === 0) {
            $this->checkForPotentialAttack($ip);
        }
    }

    /**
     * Limpia los intentos fallidos para un usuario/IP específico.
     */
    public function clearAttempts(string $identifier, string $ip): void
    {
        $key = $this->getAttemptKey($identifier, $ip);
        RateLimiter::clear($key);
    }

    /**
     * Obtiene la cantidad de intentos fallidos.
     */
    public function getAttempts(string $identifier, string $ip): int
    {
        $key = $this->getAttemptKey($identifier, $ip);
        $raw = RateLimiter::attempts($key);
        if (is_int($raw)) {
            return $raw;
        }

        if (is_string($raw) || is_float($raw)) {
            return (int) $raw;
        }

        return 0;
    }

    /**
     * Obtiene los minutos restantes hasta que se quite el bloqueo.
     */
    public function getRemainingMinutes(string $identifier, string $ip): int
    {
        $key = $this->getAttemptKey($identifier, $ip);
        $remainingSeconds = RateLimiter::availableIn($key);

        return (int) ceil($remainingSeconds / 60);
    }

    /**
     * Verifica si una IP está bloqueada por sospecha de ataque.
     */
    public function isIpBlocked(string $ip): bool
    {
        return Cache::has('ip_block:'.$ip);
    }

    /**
     * Calcula el tiempo de decaimiento en minutos basado en el número de intentos previos.
     */
    private function calculateDecayMinutes(string $identifier, string $ip): int
    {
        $attempts = $this->getAttempts($identifier, $ip);

        // Aplica un incremento exponencial al tiempo de bloqueo
        $lockoutPeriod = (int) round(
            $this->initialLockoutPeriod * $this->lockoutMultiplier ** $attempts
        );

        // Limita al máximo período de bloqueo
        return min($lockoutPeriod, $this->maxLockoutPeriod);
    }

    /**
     * Obtiene la clave para el limitador de tasa.
     */
    private function getAttemptKey(string $identifier, string $ip): string
    {
        return Str::transliterate(
            'login:'.mb_strtolower($identifier).'|'.$ip
        );
    }

    /**
     * Verifica si hay un posible ataque de fuerza bruta desde una IP específica.
     */
    private function checkForPotentialAttack(string $ip): void
    {
        // Contar intentos fallidos totales desde esta IP en diferentes cuentas
        $ipAttemptsKey = 'login_attempts_ip:'.$ip;
        $raw = Cache::get($ipAttemptsKey, 0);
        $base = 0;
        if (is_int($raw)) {
            $base = $raw;
        } elseif (is_string($raw) || is_float($raw)) {
            $base = (int) $raw;
        }

        $ipAttempts = $base + 1;

        // Almacenar por 1 hora
        Cache::put($ipAttemptsKey, $ipAttempts, 60 * 60);

        // Si hay muchos intentos desde la misma IP pero en diferentes cuentas, es sospechoso
        if ($ipAttempts >= 10) {
            Log::alert(
                'Posible ataque de fuerza bruta detectado',
                [
                    'ip' => $ip,
                    'total_attempts' => $ipAttempts,
                ]
            );

            // Aquí se podrían implementar acciones adicionales como:
            // 1. Bloquear la IP por un período extendido
            // 2. Enviar alertas a los administradores
            // 3. Agregar la IP a una lista negra temporal

            // Ejemplo de bloqueo extendido:
            $blockKey = 'ip_block:'.$ip;
            Cache::put($blockKey, true, 60 * 60 * 24); // Bloqueo por 24 horas
        }
    }
}
