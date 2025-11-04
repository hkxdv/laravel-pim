<?php

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\AuthenticatableUser;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Traits\CrossGuardPermissions;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Modelo de Usuario para el personal interno (Staff).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\StaffUsersFactory>
 */
final class StaffUsers extends Authenticatable implements AuthenticatableUser, MustVerifyEmail
{
    use CrossGuardPermissions;
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\StaffUsersFactory> */
    use HasFactory;

    use HasRoles;
    use LogsActivity;
    use Notifiable;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'staff_users';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * Los atributos que deberían estar ocultos para las serializaciones.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atributos agregados al array/JSON automáticamente.
     * Esto permite exponer 'avatar' como un atributo computado.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar',
    ];

    private string $guard_name = 'staff';

    /**
     * Verifica si el usuario está activo.
     */
    public function isActive(): bool
    {
        // Por defecto, todos los usuarios staff están activos
        // Se puede extender para verificar campos como 'is_active' o 'status'
        return true;
    }

    /**
     * Verifica si el usuario ha sido eliminado (soft delete).
     */
    public function trashed(): bool
    {
        // Este modelo no usa soft deletes por defecto
        // Se puede extender si se implementa SoftDeletes trait
        return false;
    }

    /**
     * Configura las opciones para el registro de actividad.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $eventName): string => 'El usuario ha sido '.$this->getEventDescription($eventName)
            );
    }

    /**
     * Envía la notificación de restablecimiento de contraseña.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Envía la notificación de verificación de correo electrónico.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Define la relación con la información de inicio de sesión del usuario.
     *
     * @return HasMany<StaffUsersLoginInfo, $this>
     */
    public function loginInfos(): HasMany
    {
        return $this->hasMany(StaffUsersLoginInfo::class, 'staff_user_id');
    }

    /**
     * Obtiene el nombre de visualización del usuario.
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Obtiene el guard de autenticación para el usuario.
     */
    public function getAuthGuard(): string
    {
        return $this->guard_name;
    }

    /**
     * Verifica si un intento de inicio de sesión es sospechoso (desde una IP/dispositivo no conocido).
     */
    public function isSuspiciousLogin(
        ?string $ipAddress,
        ?string $userAgent
    ): bool {
        if (
            in_array($ipAddress, [null, '', '0'], true)
            || in_array($userAgent, [null, '', '0'], true)
        ) {
            return true; // No hay datos suficientes, se considera sospechoso.
        }

        // Busca si ya tenemos registros de este dispositivo/IP o si es un dispositivo de confianza.
        $isKnown = $this->loginInfos()
            ->where(
                function ($query) use ($ipAddress, $userAgent): void {
                    $query
                        ->where('ip_address', $ipAddress)
                        ->where('user_agent', $userAgent)
                        ->orWhere('is_trusted', true);
                }
            )
            ->exists();

        return ! $isKnown;
    }

    /**
     * Registra un nuevo inicio de sesión para el usuario.
     *
     * @param  array<string, mixed>  $deviceInfo
     */
    public function recordLogin(
        ?string $ipAddress,
        ?string $userAgent,
        array $deviceInfo = []
    ): StaffUsersLoginInfo {
        /** @var StaffUsersLoginInfo $created */
        $created = $this->loginInfos()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device'] ?? null,
            'browser' => $deviceInfo['browser'] ?? null,
            'platform' => $deviceInfo['platform'] ?? null,
            'is_mobile' => $deviceInfo['is_mobile'] ?? false,
            'is_trusted' => false, // Por defecto, los nuevos dispositivos no son de confianza.
            'last_login_at' => now(),
            'login_count' => 1,
        ]);

        return $created;
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        /** @var array<string, mixed> $arr */
        $arr = $this->toArray();

        return $arr;
    }

    /**
     * Atributo computado para obtener los permisos del usuario filtrados para el frontend.
     * Excluye permisos sensibles que no deberían ser expuestos en el lado del cliente.
     *
     * @return array<string>
     */
    protected function getFrontendPermissionsAttribute(): array
    {
        // Obtiene todos los permisos únicos (directos y vía roles) a través del trait.
        $allPermissions = $this->getAllCrossGuardPermissions();

        // Define patrones de permisos a excluir del frontend.
        $excludePatterns = [
            'delete-',
            'manage-',
            'admin-',
        ];

        return array_filter(
            $allPermissions,
            function (string $permission) use ($excludePatterns): bool {
                foreach ($excludePatterns as $pattern) {
                    if (str_starts_with($permission, $pattern)) {
                        return false; // Excluir si el permiso comienza con un patrón no deseado.
                    }
                }

                return true;
            }
        );
    }

    /**
     * Accessor para obtener la URL del avatar del usuario.
     * (Se removió la dependencia a ContactStaffUser/contactProfile.)
     */
    protected function getAvatarAttribute(): string
    {
        return '';
    }

    /**
     * Obtiene los atributos que deberían ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_activity' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * Devuelve una descripción legible del evento.
     */
    private function getEventDescription(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'creado',
            'updated' => 'actualizado',
            'deleted' => 'eliminado',
            default => $eventName,
        };
    }
}
