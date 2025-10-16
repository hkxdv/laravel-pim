<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StaffUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforma el modelo StaffUser en un array para respuestas API.
 * Este recurso expone de forma segura los datos del usuario del personal,
 * incluyendo sus roles y permisos de forma condicional para optimizar el rendimiento.
 */
final class StaffUserResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StaffUsers $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'user_type' => 'staff',

            // Carga condicional de la relación 'roles' usando RoleResource para consistencia.
            'roles' => RoleResource::collection($this->whenLoaded('roles')),

            // Carga condicional de todos los permisos (directos y vía roles).
            // Se incluye solo si la relación 'roles' ha sido cargada,
            // ya que getAllPermissions() depende de ella para ser eficiente.
            'permissions' => $this->when(
                $user->relationLoaded('roles'),
                fn () => $user->getAllPermissions()->pluck('name')
            ),
        ];
    }
}
