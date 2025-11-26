<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers;

use App\Models\StaffUsers;
use Spatie\Activitylog\Models\Activity;

/**
 * Controlador principal del panel de administración.
 * Gestiona la visualización del dashboard administrativo y sus funcionalidades generales.
 *
 * NOTA: Este controlador hereda el método showModulePanel() de ModuleOrchestrationController,
 * el cual está marcado como 'final' y no debe ser sobrescrito. Para personalizar el
 * comportamiento del panel, implementa los métodos de extensión:
 * - getModuleStats(): para definir las estadísticas específicas del módulo
 * - getAdditionalPanelData(): para agregar datos adicionales al panel
 */
final class AdminPanelController extends AdminBaseController
{
    /**
     * Hook extensible: datos adicionales específicos del módulo para el panel.
     * Los controladores hijos pueden sobrescribirlo para añadir información propia.
     *
     * @return array<string, mixed> Datos adicionales a inyectar en la vista del panel
     */
    protected function getAdditionalPanelData(): array
    {
        return [
            'recentActivity' => $this->getRecentActivity(),
        ];
    }

    /**
     * Exponer las estadísticas del módulo como EnhancedStat[].
     *
     * @return \App\DTO\EnhancedStat[] Estadísticas enriquecidas del módulo o null si no aplica
     */
    protected function getModuleStats(): array
    {
        return $this->statsService->getPanelStats(
            $this->getModuleSlug(),
            $this->getAuthenticatedUser()
        );
    }

    /**
     * Obtiene la actividad reciente para mostrar en el panel de administración.
     *
     * @return array<int, array{
     *     id: int,
     *     user: array{name: string},
     *     title: string,
     *     timestamp: string,
     *     icon: string
     * }>
     */
    private function getRecentActivity(): array
    {
        $activities = Activity::with('causer')->latest()->take(5)->get();

        $result = [];
        foreach ($activities as $activity) {
            $userName = 'Sistema';
            if ($activity->causer instanceof StaffUsers) {
                $nameRaw = $activity->causer->getAttribute('name');
                $userName = is_string($nameRaw) ? $nameRaw : 'Sistema';
            }

            $timestamp = $activity->created_at ? (string) $activity->created_at->toIso8601String() : now()->toIso8601String();

            $titleRaw = $activity->description;
            $title = is_string($titleRaw) ? $titleRaw : '';

            $result[] = [
                'id' => (int) $activity->id,
                'user' => ['name' => $userName],
                'title' => $title,
                'timestamp' => $timestamp,
                'icon' => $this->getIconForEvent($activity->event),
            ];
        }

        return $result;
    }

    /**
     * Devuelve el nombre de ícono adecuado para el evento dado.
     *
     * @param  string|null  $event  Evento auditado (created, updated, deleted, etc.)
     * @return string Nombre del ícono según la semántica del evento
     */
    private function getIconForEvent(?string $event): string
    {
        $e = mb_strtolower((string) $event);

        return match ($e) {
            'created', 'create' => 'fileplus2',
            'updated', 'update' => 'pencil',
            'deleted', 'delete', 'removed', 'remove' => 'xcircle',
            'restored', 'restore' => 'checkcircle',
            'login', 'logged-in', 'logged_in', 'authenticated' => 'keyround',
            'logout', 'logged-out', 'logged_out' => 'lock',
            'role_assigned', 'role-granted', 'permission_assigned', 'permission-granted' => 'shieldcheck',
            'role_revoked', 'permission_revoked', 'permission-revoked' => 'shieldalert',
            default => 'activity',
        };
    }
}
