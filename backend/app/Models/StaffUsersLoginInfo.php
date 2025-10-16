<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo que almacena el historial de inicios de sesión del personal (Staff).
 *
 * Registra información de cada intento de inicio de sesión, como la dirección IP,
 * el agente de usuario y detalles del dispositivo, para mejorar la seguridad y
 * permitir la detección de actividades sospechosas.
 *
 * @property int $id
 * @property int $staff_user_id ID del usuario de personal asociado.
 * @property string|null $ip_address Dirección IP desde la que se inició sesión.
 * @property string|null $user_agent Agente de usuario del navegador.
 * @property string|null $device_type Tipo de dispositivo (ej. 'desktop', 'tablet').
 * @property string|null $browser Navegador utilizado.
 * @property string|null $platform Plataforma o sistema operativo.
 * @property bool $is_mobile Indica si el dispositivo es móvil.
 * @property bool $is_trusted Indica si el dispositivo es de confianza.
 * @property \Carbon\CarbonInterface|null $last_login_at Fecha y hora del último inicio de sesión.
 * @property int $login_count Contador de inicios de sesión desde este dispositivo.
 * @property-read StaffUsers $staffUser
 *
 * @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffUsersLoginInfo>>
 */
final class StaffUsersLoginInfo extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffUsersLoginInfo>> */
    use HasFactory;

    /**
     * Umbral de similitud para la comparación de agentes de usuario.
     * Se usa para tolerar variaciones menores en las versiones de los navegadores.
     */
    private const int USER_AGENT_SIMILARITY_THRESHOLD = 80;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'staff_login_infos';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'staff_user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'is_mobile',
        'is_trusted',
        'last_login_at',
        'login_count',
    ];

    /**
     * Los atributos que deberían ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_mobile' => 'boolean',
        'is_trusted' => 'boolean',
        'last_login_at' => 'datetime',
        'login_count' => 'integer',
    ];

    /**
     * Define la relación con el usuario de personal al que pertenece esta información.
     *
     * @return BelongsTo<StaffUsers, $this>
     */
    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(StaffUsers::class, 'staff_user_id');
    }

    /**
     * Determina si los datos de un nuevo inicio de sesión coinciden con este registro.
     *
     * Compara la dirección IP de forma exacta y el agente de usuario con un umbral
     * de similitud para tolerar pequeñas variaciones en las versiones del navegador.
     *
     * @param  string|null  $ip  La dirección IP del nuevo inicio de sesión.
     * @param  string|null  $userAgent  El agente de usuario del nuevo inicio de sesión.
     */
    public function matches(?string $ip, ?string $userAgent): bool
    {
        if ($this->ip_address !== $ip) {
            return false;
        }

        if ($this->user_agent === $userAgent) {
            return true;
        }

        if (in_array($userAgent, [null, '', '0'], true) || ! $this->user_agent) {
            return false;
        }

        similar_text($userAgent, $this->user_agent, $percent);

        return $percent > self::USER_AGENT_SIMILARITY_THRESHOLD;
    }

    /**
     * Actualiza los datos del último inicio de sesión y el contador.
     * Este método guarda los cambios en la base de datos.
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->increment('login_count');
        $this->save();
    }

    /**
     * ACCESOR DE COMPATIBILIDAD: Obtiene el `staff_user_id`.
     *
     * Este accesor existe para mantener la compatibilidad con partes del código
     * que podrían referirse incorrectamente a `staff_users_id` (en plural).
     * El nombre correcto de la columna es `staff_user_id`.
     */
    protected function getStaffUsersIdAttribute(): int
    {
        return $this->staff_user_id;
    }

    /**
     * MUTADOR DE COMPATIBILIDAD: Establece el `staff_user_id`.
     *
     * Este mutador permite asignar el ID de usuario utilizando el alias incorrecto
     * `staff_users_id` por razones de compatibilidad.
     */
    protected function setStaffUsersIdAttribute(int $value): void
    {
        $this->attributes['staff_user_id'] = $value;
    }
}
