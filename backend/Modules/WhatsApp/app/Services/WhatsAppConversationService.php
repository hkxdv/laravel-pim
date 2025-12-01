<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Modules\WhatsApp\App\Models\WhatsAppSession;

/**
 * Servicio encargado de gestionar el estado y ciclo de vida de las sesiones de conversación de WhatsApp.
 */
final class WhatsAppConversationService
{
    private const int DEFAULT_EXPIRE_MINUTES = 1440;

    /**
     * Obtiene o crea una sesión para un número de teléfono.
     * Verifica si la sesión ha expirado y la deshabilita si es necesario.
     *
     * @param  string  $phone  Número de teléfono.
     */
    public function get(string $phone): WhatsAppSession
    {
        /** @var WhatsAppSession $session */
        $session = WhatsAppSession::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'search_enabled' => false,
                'muted_until' => null,
                'meta' => [],
            ]
        );

        $this->checkSessionExpiration($session);

        return $session;
    }

    /**
     * Habilita la búsqueda para la sesión actual.
     *
     * @param  WhatsAppSession  $session  Sesión a actualizar.
     */
    public function enableSearch(WhatsAppSession $session): WhatsAppSession
    {
        $session->search_enabled = true;
        $this->updateMeta($session, [
            'enabled_at' => Date::now()->toISOString(),
        ]);
        $session->save();

        return $session;
    }

    /**
     * Silencia la sesión por una cantidad de minutos determinada (Legacy).
     * Determina el modo automáticamente basado en la duración.
     *
     * @param  WhatsAppSession  $session  Sesión a silenciar.
     * @param  int  $minutes  Duración en minutos (default: 1440).
     */
    public function mute(
        WhatsAppSession $session,
        int $minutes = 1440
    ): WhatsAppSession {
        $mode = match ($minutes) {
            60 => '1h',
            720 => '12h',
            1440 => '24h',
            default => 'minutes',
        };

        return $this->applyPause($session, $minutes, $mode);
    }

    /**
     * Establece una pausa indefinida (gate) pendiente de confirmación o acción.
     *
     * @param  WhatsAppSession  $session  Sesión a pausar.
     */
    public function pauseGate(WhatsAppSession $session): WhatsAppSession
    {
        return $this->applyPause($session, null, 'pending');
    }

    /**
     * Silencia la sesión por un tiempo específico y con un modo explícito.
     *
     * @param  WhatsAppSession  $session  Sesión a silenciar.
     * @param  int  $minutes  Duración en minutos.
     * @param  string  $mode  Identificador del modo de pausa.
     */
    public function muteFor(
        WhatsAppSession $session,
        int $minutes,
        string $mode
    ): WhatsAppSession {
        return $this->applyPause($session, $minutes, $mode);
    }

    /**
     * Silencia la sesión permanentemente.
     *
     * @param  WhatsAppSession  $session  Sesión a silenciar.
     */
    public function muteForever(WhatsAppSession $session): WhatsAppSession
    {
        return $this->applyPause($session, null, 'forever', true);
    }

    /**
     * Reactiva la sesión, eliminando cualquier estado de silencio o pausa.
     *
     * @param  WhatsAppSession  $session  Sesión a reactivar.
     */
    public function unmute(WhatsAppSession $session): WhatsAppSession
    {
        $session->muted_until = null;

        $this->updateMeta($session, [
            'welcome_shown' => false,
            'resume_sent_at' => null,
            'resume_sent_once' => false,
            'hard_paused' => false,
            'pause_forever' => false,
            'paused_by' => '',
            'paused_at' => null,
            'paused_minutes' => null,
            'pause_mode' => null,
        ]);

        $session->save();

        return $session;
    }

    /**
     * Verifica si la sesión está actualmente pausada o silenciada.
     */
    public function isPaused(WhatsAppSession $session): bool
    {
        $meta = is_array($session->meta) ? $session->meta : [];
        $hardPaused = (bool) ($meta['hard_paused'] ?? false);

        if ($session->muted_until && Date::now()->lt($session->muted_until)) {
            return true;
        }

        return $hardPaused;
    }

    /**
     * Marca que el mensaje de bienvenida ha sido mostrado.
     *
     * @param  WhatsAppSession  $session  Sesión a actualizar.
     */
    public function markWelcomeShown(WhatsAppSession $session): void
    {
        $this->updateMeta($session, ['welcome_shown' => true]);
        $session->save();
    }

    /**
     * Aplica la lógica común de pausa a la sesión.
     *
     * @param  WhatsAppSession  $session  Sesión a modificar.
     * @param  int|null  $minutes  Minutos de silencio (null si es indefinido o forever).
     * @param  string  $mode  Modo de pausa.
     * @param  bool  $forever  Indica si la pausa es permanente.
     */
    private function applyPause(
        WhatsAppSession $session,
        ?int $minutes,
        string $mode,
        bool $forever = false
    ): WhatsAppSession {
        if ($minutes !== null) {
            $session->muted_until = Date::now()->addMinutes($minutes);
        } elseif ($forever) {
            $session->muted_until = null; // Forever no usa muted_until temporal
        }

        // Nota: pauseGate (minutes=null, forever=false) mantiene muted_until como esté o null

        $phone = $session->phone;

        $this->updateMeta($session, [
            'resume_sent_at' => null,
            'resume_sent_once' => false,
            'hard_paused' => true,
            'pause_forever' => $forever,
            'paused_by' => is_string($phone) ? $phone : '',
            'paused_at' => Date::now()->toISOString(),
            'paused_minutes' => $minutes,
            'pause_mode' => $mode,
        ]);

        $session->save();

        return $session;
    }

    /**
     * Verifica si la sesión ha expirado basándose en 'enabled_at'.
     */
    private function checkSessionExpiration(WhatsAppSession $session): void
    {
        $expireMinRaw = Config::get(
            'services.whatsapp.gate_expire_minutes',
            self::DEFAULT_EXPIRE_MINUTES
        );
        $expireMin = is_numeric($expireMinRaw)
            ? (int) $expireMinRaw : self::DEFAULT_EXPIRE_MINUTES;

        $meta = is_array($session->meta ?? null) ? $session->meta : [];
        $enabledAtRaw = $meta['enabled_at'] ?? null;

        if (is_string($enabledAtRaw) && $enabledAtRaw !== '') {
            $enabledAt = Date::parse($enabledAtRaw);
            // Si ha expirado, deshabilitamos y persistimos el cambio para mantener la consistencia en BD.
            if (
                $enabledAt->copy()->addMinutes($expireMin)->isPast()
                && $session->search_enabled
            ) {
                $session->search_enabled = false;
                $session->save();
            }
        }
    }

    /**
     * Helper para actualizar el array de metadatos de forma segura.
     *
     * @param  array<string, mixed>  $updates
     */
    private function updateMeta(WhatsAppSession $session, array $updates): void
    {
        $currentMeta = is_array($session->meta ?? null) ? $session->meta : [];
        $session->meta = array_merge($currentMeta, $updates);
    }
}
