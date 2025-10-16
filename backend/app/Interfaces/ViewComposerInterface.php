<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

/**
 * Interfaz para la composición de datos para vistas.
 * Define cómo se preparan y estructuran los datos para renderizar vistas Inertia.
 */
interface ViewComposerInterface
{
    /**
     * Prepara los datos comunes para las vistas del módulo.
     *
     * @param  string  $moduleSlug  Slug del módulo
     * @param  array<int, array<string, mixed>>|array<string, mixed>  $panelItemsConfig  Configuración de los ítems del panel (lista de ítems o un solo ítem)
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  string  $functionalName  Nombre funcional del módulo
     * @param  array<string, mixed>|null  $stats  Estadísticas del módulo
     * @param  array<string, mixed>  $data  Datos adicionales
     * @return array<string, mixed> Datos para la vista
     */
    public function prepareModuleViewData(
        string $moduleSlug,
        array $panelItemsConfig,
        callable $permissionChecker,
        string $functionalName,
        ?array $stats = null,
        array $data = []
    ): array;

    /**
     * Método mejorado que prepara todos los datos necesarios para una vista de módulo en un solo paso.
     *
     * @param  string  $moduleSlug  Slug del módulo
     * @param  array<int, array<string, mixed>>  $panelItemsConfig  Configuración de los ítems del panel
     * @param  array<int, array<string, mixed>>  $contextualNavItemsConfig  Configuración de los ítems de navegación contextual
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  mixed  $user  Usuario autenticado
     * @param  string|null  $functionalName  Nombre funcional del módulo
     * @param  array<string, mixed>  $data  Datos adicionales
     * @param  array<string, mixed>|null  $stats  Estadísticas del módulo
     * @param  string|null  $routeSuffix  Sufijo de ruta para los breadcrumbs configurados
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta para los breadcrumbs
     * @return array<string, mixed> Datos completos para la vista
     */
    public function composeModuleViewContext(
        string $moduleSlug,
        array $panelItemsConfig,
        array $contextualNavItemsConfig,
        callable $permissionChecker,
        $user,
        ?string $functionalName = null,
        array $data = [],
        ?array $stats = null,
        ?string $routeSuffix = null,
        array $routeParams = []
    ): array;

    /**
     * Método específico para preparar datos del dashboard principal.
     *
     * @param  mixed  $user  Usuario autenticado
     * @param  array<int, mixed>  $availableModules  Módulos disponibles
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  Request  $request  Request actual
     * @return array<string, mixed> Datos para la vista del dashboard
     */
    public function composeDashboardViewContext(
        $user,
        array $availableModules,
        callable $permissionChecker,
        Request $request
    ): array;

    /**
     * Renderiza una vista de Inertia con los datos del módulo.
     *
     * @param  string  $view  Nombre de la vista
     * @param  string  $moduleViewPath  Ruta de la vista del módulo
     * @param  array<string, mixed>  $data  Datos para la vista
     * @return InertiaResponse Respuesta Inertia
     */
    public function renderModuleView(
        string $view,
        string $moduleViewPath,
        array $data = []
    ): InertiaResponse;

    /**
     * Obtiene los mensajes flash de la sesión.
     *
     * @param  Request  $request  Request actual
     * @return array<string, mixed> Mensajes flash
     */
    public function getFlashMessages(Request $request): array;
}
