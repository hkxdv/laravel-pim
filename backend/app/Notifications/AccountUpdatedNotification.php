<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de seguridad enviada cuando se actualizan los datos de la cuenta de un usuario.
 *
 * Esta notificación se pone en cola para no impactar el rendimiento. Informa al usuario
 * sobre los campos que han cambiado, mostrando los valores antiguos y nuevos cuando
 * sea aplicable, y proporciona un enlace para revisar la cuenta.
 */
final class AccountUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * @param  array<string, mixed>  $changes  Un array asociativo con los cambios realizados.
     *                                         Para cada campo, puede contener los valores 'old' y 'new'.
     * @param  string|null  $ipAddress  La dirección IP desde la que se realizó el cambio.
     */
    public function __construct(
        public array $changes = [],
        public ?string $ipAddress = null
    ) {}

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
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable  $notifiable  La entidad que recibe la notificación.
     * @return MailMessage El mensaje de correo electrónico configurado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // --- Construcción del Mensaje Principal ---
        $nameValue = $notifiable->getAttribute('name');
        $nameSafe = is_string($nameValue) ? $nameValue : '';

        $message = (new MailMessage)
            ->subject(
                'Alerta de Seguridad: Cambios en tu cuenta'
            )
            ->greeting(
                sprintf('¡Hola %s!', $nameSafe)
            )
            ->line(
                'Hemos detectado que se han realizado los siguientes cambios en tu cuenta:'
            );

        // --- Detalle de los Cambios ---
        // Itera sobre los cambios y los añade al cuerpo del correo.
        foreach ($this->changes as $field => $values) {
            if (is_array($values) && isset($values['old']) && isset($values['new'])) {
                // Si tenemos valores antiguo y nuevo, los mostramos
                $oldValue = $this->formatValue($field, $values['old']);
                $newValue = $this->formatValue($field, $values['new']);

                if ($field === 'password') {
                    $message->line('- Se ha cambiado tu contraseña.');
                } else {
                    $message->line(sprintf("- **%s**: cambiado de '%s' a '%s'.", $this->getFieldName($field), $oldValue, $newValue));
                }
            } else {
                // Si solo tenemos el campo que cambió
                $message->line(sprintf('- Se actualizó: **%s**.', $this->getFieldName($field)));
            }
        }

        // --- Información Adicional y Acciones ---
        $message->line(
            'Estos cambios se realizaron el '.now()->format('d/m/Y').' a las '.now()->format('H:i:s').'.'
        );

        if (! in_array($this->ipAddress, [null, '', '0'], true)) {
            $message->line(
                sprintf('Cambios realizados desde la dirección IP: %s.', $this->ipAddress)
            );
        }

        $message->line(
            'Si no reconoces estos cambios, por favor contacta inmediatamente con soporte.'
        )
            ->action(
                'Ir a mi cuenta',
                route('settings.profile')
            )
            ->line(
                'Este es un correo electrónico automático de seguridad. Por favor, no respondas a este mensaje.'
            );

        return $message;
    }

    /**
     * Obtiene la representación de la notificación como un array.
     *
     * Esto es útil para almacenar la notificación en la base de datos o para enviarla
     * a través de canales que no son de correo, como Web Push.
     *
     * @param  \Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable  $notifiable  La entidad que recibe la notificación.
     * @return array<string, mixed> Los datos de la notificación.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'changes' => $this->changes,
            'ip_address' => $this->ipAddress,
            'time' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtiene un nombre de campo legible para el usuario.
     *
     * Este método de ayuda traduce los nombres de los campos de la base de datos
     * (ej. 'profile_photo') a un formato más amigable para el usuario (ej. 'Foto de perfil').
     *
     * @param  string  $field  El nombre del campo de la base de datos.
     * @return string El nombre del campo formateado para el usuario.
     */
    private function getFieldName(string $field): string
    {
        $names = [
            'name' => 'Nombre',
            'email' => 'Correo electrónico',
            'password' => 'Contraseña',
            'phone' => 'Teléfono',
            'address' => 'Dirección',
            'role' => 'Rol de usuario',
            'permissions' => 'Permisos',
            'profile_photo' => 'Foto de perfil',
        ];

        return $names[$field] ?? ucfirst($field);
    }

    /**
     * Formatea un valor para su visualización segura en el correo electrónico.
     *
     * Este método de ayuda se encarga de ofuscar datos sensibles como las contraseñas
     * y de convertir arrays en una cadena legible.
     *
     * @param  string  $field  El nombre del campo al que pertenece el valor.
     * @param  mixed  $value  El valor a formatear.
     * @return string El valor formateado y seguro para mostrar.
     */
    private function formatValue(string $field, $value): string
    {
        if ($field === 'password') {
            return '********';
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '[dato]';
    }
}
