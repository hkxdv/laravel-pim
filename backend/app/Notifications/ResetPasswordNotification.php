<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación para el restablecimiento de contraseña.
 *
 * Esta notificación se envía cuando un usuario solicita restablecer su contraseña.
 * Contiene un enlace seguro y con tiempo de expiración para que el usuario pueda
 * crear una nueva contraseña. Hereda la funcionalidad base de Laravel para
 * esta tarea, pero está estandarizada para el proyecto.
 */
final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * El callback que se debe usar para construir el mensaje de correo.
     *
     * @var (callable(\Illuminate\Contracts\Auth\CanResetPassword, string): MailMessage)|null
     */
    public static $toMailCallback;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * @param  string  $token  El token de restablecimiento de contraseña.
     */
    public function __construct(public string $token) {}

    /**
     * Define un callback para personalizar la construcción del mensaje de correo.
     *
     * Esto permite modificar la lógica de envío de correo desde un Service Provider
     * sin tener que sobreescribir toda la clase de notificación.
     *
     * @param  callable(\Illuminate\Contracts\Auth\CanResetPassword, string): MailMessage  $callback
     */
    public static function toMailUsing(mixed $callback): void
    {
        self::$toMailCallback = $callback;
    }

    /**
     * Obtiene los canales de entrega de la notificación.
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
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Contracts\Auth\CanResetPassword  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Si se ha definido un callback personalizado, se utiliza para construir el mensaje.
        if (self::$toMailCallback) {
            return call_user_func(
                self::$toMailCallback,
                $notifiable,
                $this->token
            );
        }

        // Obtiene el tiempo de expiración del token desde la configuración.
        $defaultBroker = config('auth.defaults.passwords');
        $broker = is_string($defaultBroker) ? $defaultBroker : 'users';
        $expireRaw = config('auth.passwords.'.$broker.'.expire');
        $expirationInMinutes = is_numeric($expireRaw) ? (int) $expireRaw : 60;

        // Construye el mensaje de correo estándar.
        $nameValue = $notifiable->getAttribute('name');
        $displayName = is_string($nameValue) ? $nameValue : 'Usuario';

        return (new MailMessage)
            ->subject(
                'Notificación de Restablecimiento de Contraseña'
            )
            ->greeting(
                sprintf('¡Hola, %s!', $displayName)
            )
            ->line(
                'Estás recibiendo este correo porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta.'
            )
            ->action(
                'Restablecer Contraseña',
                $this->resetUrl($notifiable)
            )
            ->line(
                sprintf('Este enlace de restablecimiento de contraseña expirará en %d minutos.', $expirationInMinutes)
            )
            ->line(
                'Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.'
            )
            ->line(
                'Este es un correo electrónico generado automáticamente. Por favor, no respondas a este mensaje.'
            )
            ->salutation('Saludos,');
    }

    /**
     * Obtiene la representación de la notificación como un array.
     *
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Contracts\Auth\CanResetPassword  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $defaultBroker = config('auth.defaults.passwords');
        $broker = is_string($defaultBroker)
            ? $defaultBroker
            : 'users';
        $expireRaw = config('auth.passwords.'.$broker.'.expire');
        $expirationInMinutes = is_numeric($expireRaw)
            ? (int) $expireRaw
            : 60;

        return [
            'message' => 'Se ha solicitado un restablecimiento de contraseña.',
            'expires_at' => now()->addMinutes($expirationInMinutes)->toIso8601String(),
        ];
    }

    /**
     * Genera la URL de restablecimiento de contraseña para el usuario notificado.
     *
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Contracts\Auth\CanResetPassword  $notifiable
     */
    private function resetUrl(object $notifiable): string
    {
        $email = $notifiable->getEmailForPasswordReset();

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));
    }
}
