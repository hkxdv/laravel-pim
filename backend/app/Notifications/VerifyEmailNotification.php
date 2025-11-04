<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Notificación para la verificación del correo electrónico del usuario.
 *
 * Esta notificación se envía cuando se crea una nueva cuenta o cuando un usuario
 * necesita verificar su dirección de correo. Extiende la funcionalidad base de Laravel
 * para generar una URL firmada y con tiempo de expiración que el usuario debe
 * visitar para activar su cuenta.
 */
final class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Construye la representación por correo electrónico de la notificación.
     *
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Contracts\Auth\MustVerifyEmail  $notifiable  La entidad que recibe la notificación
     * @return MailMessage El mensaje de correo electrónico configurado.
     */
    public function toMail($notifiable): MailMessage
    {
        // Genera la URL de verificación única para este usuario.
        $verificationUrl = $this->verificationUrl($notifiable);

        // Construye el mensaje de correo.
        $nameValue = $notifiable->getAttribute('name');
        $displayName = is_string($nameValue) ? $nameValue : 'Usuario';

        return (new MailMessage)
            ->subject(
                'Activación de Cuenta y Verificación de Correo'
            )
            ->greeting(
                sprintf('¡Hola, %s!', $displayName)
            )
            ->line(
                'Se ha creado una cuenta para ti en nuestro sistema.'
            )
            ->line(
                'Para activar tu cuenta y comenzar, por favor verifica tu dirección de correo electrónico haciendo clic en el botón de abajo:'
            )
            ->action(
                'Verificar Correo Electrónico',
                $verificationUrl
            )
            ->line(
                'Si no esperabas la creación de esta cuenta, puedes ignorar este mensaje de forma segura.'
            )
            ->line(
                'Este correo electrónico es generado automáticamente. Por favor, no respondas a este mensaje.'
            )
            ->salutation('Saludos,');
    }

    /**
     * Genera la URL de verificación para el usuario notificado.
     *
     * Este método crea una URL temporal y firmada que es única para cada usuario,
     * garantizando que solo el destinatario pueda verificar la cuenta. La duración
     * de la validez del enlace se obtiene de la configuración de la aplicación.
     *
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Contracts\Auth\MustVerifyEmail  $notifiable  La entidad notificada
     * @return string La URL de verificación firmada.
     */
    protected function verificationUrl($notifiable): string
    {
        $expireRaw = Config::get('auth.verification.expire', 60);
        $expireMinutes = is_numeric($expireRaw)
            ? (int) $expireRaw
            : 60;
        $email = $notifiable->getEmailForVerification();

        return URL::temporarySignedRoute(
            'verification.verify',
            \Illuminate\Support\Facades\Date::now()->addMinutes($expireMinutes),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($email),
            ]
        );
    }
}
