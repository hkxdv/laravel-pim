<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Services;

use App\Models\WhatsAppSession;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;

final class WhatsAppConversationService
{
    public function get(string $phone): WhatsAppSession
    {
        /** @var WhatsAppSession|null $session */
        $session = WhatsAppSession::query()->where('phone', $phone)->first();
        if ($session === null) {
            $session = WhatsAppSession::query()->create([
                'phone' => $phone,
                'search_enabled' => false,
                'muted_until' => null,
                'meta' => [],
            ]);
        }

        $expireMinRaw = Config::get('services.whatsapp.gate_expire_minutes', 1440);

        $expireMin = is_numeric($expireMinRaw)
            ? (int) $expireMinRaw : 1440;
        $meta = is_array($session->meta ?? null)
            ? $session->meta : [];

        $enabledAtRaw = $meta['enabled_at'] ?? null;
        if (is_string($enabledAtRaw) && $enabledAtRaw !== '') {
            $enabledAt = Date::parse($enabledAtRaw);
            if ($enabledAt->copy()->addMinutes($expireMin)->isPast()) {
                $session->search_enabled = false;
            }
        }

        return $session;
    }

    public function enableSearch(WhatsAppSession $session): WhatsAppSession
    {
        $session->search_enabled = true;
        $meta = is_array($session->meta ?? null)
            ? $session->meta : [];
        $meta['enabled_at'] = Date::now()->toISOString();
        $session->meta = $meta;
        $session->save();

        return $session;
    }

    public function mute(
        WhatsAppSession $session,
        int $minutes = 1440
    ): WhatsAppSession {
        $session->muted_until = Date::now()
            ->addMinutes($minutes)
            ->toImmutable();
        $meta = is_array($session->meta ?? null) ? $session->meta : [];
        $meta['resume_sent_at'] = null;
        $meta['resume_sent_once'] = false;
        $session->meta = $meta;
        $session->save();

        return $session;
    }

    public function unmute(WhatsAppSession $session): WhatsAppSession
    {
        $session->muted_until = null;
        $meta = is_array($session->meta ?? null)
            ? $session->meta : [];
        $meta['welcome_shown'] = false;
        $meta['resume_sent_at'] = null;
        $meta['resume_sent_once'] = false;
        $session->meta = $meta;
        $session->save();

        return $session;
    }

    public function markWelcomeShown(WhatsAppSession $session): void
    {
        $meta = is_array($session->meta ?? null)
            ? $session->meta : [];
        $meta['welcome_shown'] = true;
        $session->meta = $meta;
        $session->save();
    }
}
