<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Notificación de seguridad enviada cuando se detecta un inicio de sesión desde un nuevo dispositivo.
 *
 * Esta notificación se pone en cola para no afectar el rendimiento de la solicitud de inicio de sesión.
 * Proporciona al usuario detalles sobre el inicio de sesión y acciones rápidas para asegurar su cuenta,
 * como marcar el dispositivo como confiable o cambiar la contraseña.
 */
final class AccountLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * La fecha y hora en que ocurrió el inicio de sesión.
     */
    public \Carbon\CarbonInterface $time;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * Utiliza la promoción de propiedades de PHP 8 para una asignación más limpia.
     * La hora del evento se captura en el momento de la instanciación.
     *
     * @param  string|null  $ipAddress  La dirección IP desde la que se originó el inicio de sesión.
     * @param  string|null  $userAgent  El agente de usuario (navegador/dispositivo) del inicio de sesión.
     * @param  string  $location  La ubicación geográfica aproximada del inicio de sesión.
     * @param  int|null  $loginId  El ID del registro de inicio de sesión para permitir marcarlo como confiable.
     */
    public function __construct(
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public string $location = 'Ubicación desconocida',
        public ?int $loginId = null
    ) {
        $this->time = now();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye la representación por correo electrónico de la notificación.
     *
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable  $notifiable  La entidad que recibe la notificación (generalmente el usuario).
     * @return MailMessage El mensaje de correo electrónico configurado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // --- Construcción del Mensaje Principal ---
        $nameValue = $notifiable->getAttribute('name');
        $nameSafe = is_string($nameValue) ? $nameValue : '';

        $message = (new MailMessage)
            ->subject(
                '¡Alerta de seguridad! Nuevo dispositivo detectado'
            )
            ->greeting(
                sprintf('¡Hola %s!', $nameSafe)
            )
            ->line(
                '**Hemos detectado un inicio de sesión desde un dispositivo o ubicación que no habías usado antes.**'
            )
            ->line(
                'Si fuiste tú, no hay problema. Si no reconoces esta actividad, tu cuenta podría estar en riesgo.'
            )
            ->line(
                '**Detalles del inicio de sesión:**'
            )
            ->line(
                '- **Fecha y hora:** '.$this->time->format('d/m/Y H:i:s')
            )
            ->when(
                $this->ipAddress,
                fn ($message) => $message->line(
                    '- **Dirección IP:** '.$this->ipAddress
                )
            )
            ->when(
                $this->userAgent,
                fn ($message) => $message->line(
                    '- **Dispositivo:** '.$this->userAgent
                )
            )
            ->line(
                '- **Ubicación aproximada:** '.$this->location
            );

        // --- Acción para Marcar Dispositivo como Confiable (Opcional) ---
        if ($this->loginId !== null && $this->loginId !== 0) {
            $trustUrl = URL::temporarySignedRoute(
                'internal.login.trust-device',
                now()->addDays(7),
                ['id' => $this->loginId]
            );

            $message->line('**¿Fuiste tú?**')
                ->line(
                    'Si reconoces este inicio de sesión, puedes marcarlo como dispositivo de confianza para no recibir más alertas cuando lo uses:'
                )
                ->action(
                    'Este dispositivo es mío',
                    $trustUrl
                );
        }

        // --- Recomendaciones de Seguridad y Acciones ---
        $message->line('**¿No fuiste tú?**')
            ->line(
                'Si no reconoces esta actividad, te recomendamos:'
            )
            ->line(
                '1. Cambiar tu contraseña inmediatamente.'
            )
            ->line(
                '2. Revisar la configuración de seguridad de tu cuenta.'
            )
            ->line(
                '3. Contactar con soporte si necesitas ayuda.'
            )
            ->action(
                'Cambiar mi contraseña',
                route('password.request')
            )
            ->line(
                'Este es un correo electrónico automático de seguridad. Por favor, no respondas a este mensaje.'
            );

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'time' => $this->time->toIso8601String(),
            'location' => $this->location,
            'login_id' => $this->loginId,
        ];
    }
}
