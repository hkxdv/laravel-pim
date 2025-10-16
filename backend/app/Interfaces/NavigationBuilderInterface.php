<?php

declare(strict_types=1);

namespace App\Interfaces;

use Nwidart\Modules\Laravel\Module;

/**
 * Interfaz para la construcción de elementos de navegación del sistema.
 * Define cómo se deben construir los diferentes tipos de navegación y estructuras relacionadas.
 */
interface NavigationBuilderInterface
{
    /**
     * Tipo de elemento de navegación: contextual.
     */
    public const NAV_TYPE_CONTEXTUAL = 'contextual';

    /**
     * Tipo de elemento de navegación: panel.
     */
    public const NAV_TYPE_PANEL = 'panel';

    /**
     * Tipo de elemento de navegación: global.
     */
    public const NAV_TYPE_GLOBAL = 'global';

    /**
     * Construye elementos de navegación basados en el tipo y configuración.
     *
     * @param  string  $navType  Tipo de navegación (usar constantes NAV_TYPE_*)
     * @param  array<int, array<string, mixed>>  $itemsConfig  Configuración de los ítems
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  string  $moduleSlug  Slug del módulo
     * @param  string|null  $functionalName  Nombre funcional del módulo
     * @return array<int, array<string, mixed>> Ítems de navegación construidos
     */
    public function buildNavigation(
        string $navType,
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array;

    /**
     * Construye los ítems de navegación contextual.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig  Configuración de los ítems
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  string  $moduleSlug  Slug del módulo
     * @param  string|null  $functionalName  Nombre funcional del módulo
     * @return array<int, array<string, mixed>> Ítems de navegación contextual
     */
    public function buildContextualNavItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array;

    /**
     * Construye los ítems del panel principal de un módulo.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig  Configuración de los ítems
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  string  $moduleSlug  Slug del módulo
     * @param  string|null  $functionalName  Nombre funcional del módulo
     * @return array<int, array<string, mixed>> Ítems del panel
     */
    public function buildPanelItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array;

    /**
     * Construye los ítems de navegación para los módulos visibles en la barra lateral.
     *
     * @param  array<Module>  $modules  Módulos disponibles
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @return array<int, array<string, mixed>> Ítems de navegación
     */
    public function buildNavItems(
        array $modules,
        callable $permissionChecker
    ): array;

    /**
     * Construye las tarjetas de módulos para el dashboard.
     *
     * @param  array<Module>  $allModules  Todos los módulos habilitados
     * @param  array<Module>  $accessibleModules  Módulos a los que el usuario tiene acceso
     * @return array<int, array<string, mixed>> Tarjetas de módulos
     */
    public function buildModuleCards(
        array $allModules,
        array $accessibleModules = []
    ): array;

    /**
     * Construye breadcrumbs a partir de una configuración explícita.
     *
     * @param  string  $moduleSlug  Slug del módulo
     * @param  string  $routeSuffix  Sufijo de ruta para identificar el conjunto correcto de breadcrumbs
     * @param  array<string, mixed>  $routeParams  Parámetros para las rutas
     * @param  array<string, mixed>  $viewData  Datos para títulos dinámicos
     * @return array<int, array<string, mixed>> Breadcrumbs
     */
    public function buildConfiguredBreadcrumbs(
        string $moduleSlug,
        string $routeSuffix,
        array $routeParams = [],
        array $viewData = []
    ): array;

    /**
     * Resuelve referencias en la configuración del formato '$ref:path.to.component'.
     *
     * @param  mixed  $config  Configuración con posibles referencias
     * @param  array<string, mixed>  $moduleConfig  Configuración completa del módulo
     * @return mixed Configuración con referencias resueltas
     */
    public function resolveConfigReferences(
        $config,
        array $moduleConfig
    ): mixed;

    /**
     * Prepara todos los elementos de navegación necesarios para una vista.
     *
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  string|null  $moduleSlug  Slug del módulo
     * @param  array<int, array<string, mixed>>  $contextualItemsConfig  Configuración contextual
     * @param  mixed  $user  Usuario autenticado
     * @param  string|null  $functionalName  Nombre funcional del módulo
     * @param  string|null  $routeSuffix  Sufijo de ruta actual
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta
     * @param  array<string, mixed>  $viewData  Datos adicionales de la vista
     * @return array<string, mixed> Estructura completa de navegación
     */
    public function assembleNavigationStructure(
        callable $permissionChecker,
        ?string $moduleSlug = null,
        array $contextualItemsConfig = [],
        $user = null,
        ?string $functionalName = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $viewData = []
    ): array;
}
