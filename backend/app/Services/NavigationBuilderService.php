<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ContextualNavItem;
use App\DTO\PanelItem;
use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Models\StaffUsers;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Laravel\Module;

/**
 * Servicio para la construcción de elementos de navegación del sistema.
 */
final readonly class NavigationBuilderService implements NavigationBuilderInterface
{
    /**
     * Mapeo de tipos de navegación a sus configuraciones específicas.
     *
     * @var array<string, array{
     *   textKey: string,
     *   textTemplateKey: string,
     *   extraFields: array<string, string>
     * }>
     */
    private const array NAV_TYPE_CONFIG = [
        self::NAV_TYPE_CONTEXTUAL => [
            'textKey' => 'title',
            'textTemplateKey' => 'title_template',
            'extraFields' => [
                'href' => 'route',
                'current' => 'current',
            ],
        ],
        self::NAV_TYPE_PANEL => [
            'textKey' => 'name',
            'textTemplateKey' => 'name_template',
            'extraFields' => [
                'route_name' => 'route_name',
                'description' => 'description',
            ],
        ],
        self::NAV_TYPE_GLOBAL => [
            'textKey' => 'title',
            'textTemplateKey' => 'title_template',
            'extraFields' => [
                'href' => 'route',
                'current' => 'current',
            ],
        ],
    ];

    /**
     * Constructor de NavigationBuilderService.
     */
    public function __construct(
        private ModuleRegistryInterface $moduleRegistry
    ) {}

    /**
     * Construye elementos de navegación basados en el tipo y configuración.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return array<int, array<string, mixed>>
     */
    public function buildNavigation(
        string $navType,
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        // Verificar que el tipo de navegación es válido
        if (! isset(self::NAV_TYPE_CONFIG[$navType])) {
            Log::warning('Tipo de navegación desconocido: '.$navType);

            return [];
        }

        // Resolver referencias en la configuración si existen
        $moduleConfig = $this->moduleRegistry
            ->getModuleConfig($moduleSlug);
        $resolvedConfig = $this
            ->resolveConfigReferences($itemsConfig, $moduleConfig);
        // Asegurar que la configuración resuelta sea un array secuencial de ítems
        $resolvedConfig = is_array($resolvedConfig)
            ? array_values($resolvedConfig)
            : [];
        /** @var array<int, array<string, mixed>> $resolvedConfig */
        $resolvedConfig = array_values(
            array_filter(
                $resolvedConfig,
                is_array(...)
            )
        );

        // Obtener la configuración específica para el tipo de navegación
        $config = self::NAV_TYPE_CONFIG[$navType];

        return $this->buildItems(
            $resolvedConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName,
            $config['textKey'],
            $config['textTemplateKey'],
            $config['extraFields']
        );
    }

    /**
     * Método de compatibilidad para buildContextualNavItems
     */
    public function buildContextualNavItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return $this->buildNavigation(
            self::NAV_TYPE_CONTEXTUAL,
            $itemsConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName
        );
    }

    /**
     * Construye los ítems del panel principal.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return array<int, array<string, mixed>>
     */
    public function buildPanelItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return $this->buildNavigation(
            self::NAV_TYPE_PANEL,
            $itemsConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName
        );
    }

    /**
     * Construye los ítems de navegación para los módulos visibles en la barra lateral.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        $navItems = [];
        $moduleItems = [];

        foreach ($modules as $module) {
            $moduleName = mb_strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleName);
            // Verificar si el módulo debe mostrarse en la navegación
            if (! isset($config['nav_item'])) {
                continue;
            }

            if (! is_array($config['nav_item'])) {
                continue;
            }

            if (! ($config['nav_item']['show_in_nav'] ?? false)) {
                continue;
            }

            // Verificar permiso
            $navItem = $config['nav_item'];
            $permission = $config['base_permission'] ?? null;

            if (! $permission || $permissionChecker($permission)) {
                $routeName = isset($navItem['route_name']) && is_string($navItem['route_name'])
                    ? $navItem['route_name']
                    : null;

                $item = [
                    'title' => isset($config['functional_name']) && is_string($config['functional_name'])
                        ? $config['functional_name']
                        : $moduleName,
                    'href' => $routeName !== null ? $this->generateRoute($routeName) : '#',
                    'icon' => isset($navItem['icon']) && is_string($navItem['icon']) ? $navItem['icon'] : null,
                    'current' => $routeName !== null && $this->isCurrentRoute($routeName),
                ];

                // Determinar si el módulo debe aparecer en la navegación principal o en la sección de módulos
                if ($navItem['show_in_main_nav'] ?? false) {
                    $navItems[] = $item;
                } else {
                    $moduleItems[] = $item;
                }
            }
        }

        // Devolver solo los ítems de navegación principal
        // Los ítems de módulos se pasarán como contextualNavItems
        return $navItems;
    }

    /**
     * Construye los ítems de navegación para los módulos que deben mostrarse en la sección de módulos.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        $moduleItems = [];

        foreach ($modules as $module) {
            $moduleName = mb_strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleName);
            // Verificar si el módulo debe mostrarse en la navegación
            if (! isset($config['nav_item'])) {
                continue;
            }

            if (! is_array($config['nav_item'])) {
                continue;
            }

            if (! ($config['nav_item']['show_in_nav'] ?? false)) {
                continue;
            }

            // Verificar permiso
            $navItem = $config['nav_item'];
            $permission = $config['base_permission'] ?? null;

            // Solo incluir si no está marcado para la navegación principal
            if ((! $permission || $permissionChecker($permission)) && ! ($navItem['show_in_main_nav'] ?? false)) {
                $routeName = isset($navItem['route_name'])
                    && is_string($navItem['route_name'])
                    ? $navItem['route_name'] : null;
                $moduleItems[] = [
                    'title' => isset($config['functional_name'])
                        && is_string($config['functional_name'])
                        ? $config['functional_name'] : $moduleName,
                    'href' => $routeName !== null
                        ? $this->generateRoute($routeName) : '#',
                    'icon' => isset($navItem['icon'])
                        && is_string($navItem['icon'])
                        ? $navItem['icon'] : null,
                    'current' => $routeName !== null && $this->isCurrentRoute($routeName),
                ];
            }
        }

        return $moduleItems;
    }

    /**
     * Construye las tarjetas de módulos para el dashboard.
     *
     * @param  array<Module>  $allModules
     * @param  array<Module>  $accessibleModules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleCards(
        array $allModules,
        array $accessibleModules = []
    ): array {
        $moduleCards = [];

        // Set de nombres de módulos accesibles en minúsculas para comparación rápida
        $accessibleNames = [];
        foreach ($accessibleModules as $am) {
            $accessibleNames[mb_strtolower($am->getName())] = true;
        }

        foreach ($allModules as $module) {
            $moduleNameLower = mb_strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleNameLower);
            if (! isset($config['nav_item'])) {
                continue;
            }

            if (! is_array($config['nav_item'])) {
                continue;
            }

            $routeName = isset($config['nav_item']['route_name']) && is_string($config['nav_item']['route_name'])
                ? $config['nav_item']['route_name']
                : null;
            $canAccess = isset($accessibleNames[$moduleNameLower]);

            $moduleCards[] = [
                'name' => isset($config['functional_name']) && is_string($config['functional_name'])
                    ? $config['functional_name']
                    : $module->getName(),
                'description' => $config['description'] ?? '',
                'href' => $routeName !== null ? $this->generateRoute($routeName) : '#',
                'icon' => isset($config['nav_item']['icon']) && is_string($config['nav_item']['icon'])
                    ? $config['nav_item']['icon']
                    : null,
                'canAccess' => $canAccess,
            ];
        }

        return $moduleCards;
    }

    /**
     * Verifica si la ruta actual coincide con el patrón dado.
     */
    public function isCurrentRoute(string $routeName): bool
    {
        $currentRoute = Route::currentRouteName();

        if (! $currentRoute) {
            return false;
        }

        // Verificar coincidencia exacta o si la ruta actual comienza con el nombre de la ruta base
        return $currentRoute === $routeName
            || str_starts_with($currentRoute, $routeName.'.');
    }

    /**
     * Construye los breadcrumbs a partir de una configuración explícita.
     *
     * @return array<int, array{title: string, href: string}>
     */
    public function buildConfiguredBreadcrumbs(
        string $moduleSlug,
        string $routeSuffix,
        array $routeParams = [],
        array $viewData = []
    ): array {
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        /** @var array<string, mixed> $moduleConfig */
        $moduleConfigArr = $moduleConfig;

        // Verificar si existen breadcrumbs configurados para esta ruta
        if (
            ! isset($moduleConfigArr['breadcrumbs'])
            || ! is_array($moduleConfigArr['breadcrumbs'])
            || ! isset($moduleConfigArr['breadcrumbs'][$routeSuffix])
        ) {
            // Si no hay configuración específica, devolvemos al menos el breadcrumb del módulo principal
            return [[
                'title' => (isset($moduleConfigArr['functional_name'])
                    && is_string($moduleConfigArr['functional_name']))
                    ? $moduleConfigArr['functional_name'] : ucfirst($moduleSlug),
                'href' => $this->generateRoute(sprintf('internal.%s.panel', $moduleSlug)),
            ]];
        }

        // Obtener la configuración de breadcrumbs y resolver las referencias
        $breadcrumbsConfig = $moduleConfigArr['breadcrumbs'][$routeSuffix];
        $resolvedBreadcrumbsConfig = $this
            ->resolveConfigReferences(
                $breadcrumbsConfig,
                $moduleConfig,
                $routeParams
            );

        if (! is_array($resolvedBreadcrumbsConfig)) {
            return [[
                'title' => (isset($moduleConfigArr['functional_name'])
                    && is_string($moduleConfigArr['functional_name']))
                    ? $moduleConfigArr['functional_name'] : ucfirst($moduleSlug),
                'href' => $this->generateRoute(sprintf('internal.%s.panel', $moduleSlug)),
            ]];
        }

        $breadcrumbs = [];

        foreach ($resolvedBreadcrumbsConfig as $config) {
            if (! is_array($config)) {
                // Ignorar elementos inválidos
                continue;
            }

            $title = isset($config['title']) && is_string($config['title'])
                ? $config['title']
                : '';

            // Manejar títulos dinámicos (acepta dynamic_title y dynamic_title_prop)
            $dynamicKey = isset($config['dynamic_title'])
                ? 'dynamic_title'
                : (isset($config['dynamic_title_prop'])
                    ? 'dynamic_title_prop'
                    : null
                );

            if (
                $dynamicKey
                && isset($config[$dynamicKey])
                && is_string($config[$dynamicKey])
                && $config[$dynamicKey] !== ''
            ) {
                $dynamicTitle = $this->extractDynamicTitle(
                    $config[$dynamicKey],
                    $viewData
                );

                if ($dynamicTitle !== null) {
                    $title = $title.': '.$dynamicTitle;
                }
            }

            // Determinar href con prioridad: href directo -> route_name/route_name_suffix -> '#'
            if (
                isset($config['href'])
                && is_string($config['href'])
                && $config['href'] !== ''
            ) {
                $href = $config['href'];
            } else {
                $routeName = isset($config['route_name']) && is_string($config['route_name'])
                    ? $config['route_name']
                    : null;

                if (in_array($routeName, [null, '', '0'], true)) {
                    $routeNameSuffix = isset($config['route_name_suffix']) && is_string($config['route_name_suffix'])
                        ? $config['route_name_suffix']
                        : null;
                    $routeName = in_array($routeNameSuffix, [null, '', '0'], true)
                        ? null
                        : sprintf('internal.%s.%s', $moduleSlug, $routeNameSuffix);
                }

                // Preferir route_params, luego route_parameters (de configs con placeholders)
                $routeParams = isset($config['route_params'])
                    ? (array) $config['route_params']
                    : (isset($config['route_parameters'])
                        ? (array) $config['route_parameters']
                        : []
                    );

                // Normalizar parámetros de ruta a array<string, mixed>
                $routeParams = $this->normalizeRouteParameters($routeParams);

                $href = $routeName !== null
                    ? $this->generateRoute($routeName, $routeParams)
                    : '#';
            }

            $breadcrumbs[] = [
                'title' => $title,
                'href' => $href,
            ];
        }

        /** @var array<int, array{title: string, href: string}> $breadcrumbs */
        return $breadcrumbs;
    }

    /**
     * Resuelve referencias en la configuración del formato '$ref:path.to.component'.
     *
     * @param  mixed  $item  Configuración con posibles referencias
     * @param  array<string, mixed>  $config  Configuración completa del módulo
     * @param  array<string, mixed>  $routeParams  Parámetros adicionales para las rutas
     * @return mixed Configuración con referencias resueltas
     */
    public function resolveConfigReferences(
        mixed $item,
        array $config,
        array $routeParams = []
    ): mixed {
        // Si es un string y comienza con '$ref:', resolverlo
        if (is_string($item) && str_starts_with($item, '$ref:')) {
            $path = mb_substr($item, 5); // Remover '$ref:'
            $parts = explode('.', $path);
            $value = $config;

            // Intentar primero la ruta directa (validando tipos)
            $directPathFound = true;
            foreach ($parts as $part) {
                if (! is_array($value) || ! array_key_exists($part, $value)) {
                    $directPathFound = false;
                    break;
                }

                $value = $value[$part];
            }

            // Si la ruta directa no funciona, intentar buscar en ubicaciones alternativas
            if (! $directPathFound) {
                // Caso 1: Referencias a links (ej: $ref:links.panel)
                if (count($parts) >= 2 && $parts[0] === 'links') {
                    // Construir la ruta alternativa con nav_components.links
                    $alternativePath = ['nav_components', 'links', $parts[1]];

                    // Añadir cualquier parte adicional después de links.xxx
                    if (count($parts) > 2) {
                        $alternativePath = array_merge(
                            $alternativePath,
                            array_slice($parts, 2)
                        );
                    }

                    // Intentar la ruta alternativa
                    $value = $config;
                    $alternativeFound = true;

                    foreach ($alternativePath as $part) {
                        if (! is_array($value) || ! array_key_exists($part, $value)) {
                            $alternativeFound = false;
                            break;
                        }

                        $value = $value[$part];
                    }

                    if ($alternativeFound) {
                        $directPathFound = true; // Marcar como encontrado
                    }
                }

                // Caso 2: Referencias a grupos (ej: $ref:groups.user_management)
                if (
                    ! $directPathFound && count($parts) >= 2
                    && $parts[0] === 'groups'
                ) {
                    // Construir la ruta alternativa con nav_components.groups
                    $alternativePath = ['nav_components', 'groups', $parts[1]];

                    // Añadir cualquier parte adicional después de groups.xxx
                    if (count($parts) > 2) {
                        $alternativePath = array_merge(
                            $alternativePath,
                            array_slice($parts, 2)
                        );
                    }

                    // Intentar la ruta alternativa
                    $value = $config;
                    $alternativeFound = true;

                    foreach ($alternativePath as $part) {
                        if (! is_array($value) || ! array_key_exists($part, $value)) {
                            $alternativeFound = false;
                            break;
                        }

                        $value = $value[$part];
                    }

                    if ($alternativeFound) {
                        $directPathFound = true; // Marcar como encontrado
                    }
                }
            }

            // Si ninguna ruta funcionó, registrar advertencia
            if (! $directPathFound) {
                Log::warning(
                    sprintf('Referencia no encontrada: %s (intentadas rutas alternativas)', $item)
                );

                return $item;
            }

            // Si encontramos un array, resolver referencias recursivamente
            if (is_array($value)) {
                return $this->resolveConfigReferences(
                    $value,
                    $config,
                    $routeParams
                );
            }

            // Si el valor es una ruta y tenemos parámetros, aplicarlos
            if (
                is_string($value)
                && str_starts_with($value, 'internal.')
                && $routeParams !== []
            ) {
                // Generar la URL con los parámetros proporcionados
                return $this->generateRoute($value, $routeParams);
            }

            return $value;
        }

        // Si es un array asociativo, verificar si tiene route_parameters y aplicar los parámetros de ruta
        if (
            is_array($item)
            && $this->isAssociativeArray($item)
            && isset($item['route_parameters'])
            && is_array($item['route_parameters'])
            && $routeParams !== []
        ) {
            // Procesar los parámetros de ruta
            $processedRouteParams = [];
            foreach ($item['route_parameters'] as $key => $value) {
                // Si el valor comienza con ':', buscar el valor real en $routeParams
                if (
                    is_string($value)
                    && str_starts_with($value, ':')
                ) {
                    $paramName = mb_substr($value, 1); // Remover ':'
                    if (
                        isset($routeParams[$paramName])
                    ) {
                        $processedRouteParams[$key] = $routeParams[$paramName];
                    } else {
                        // Si no se encuentra el parámetro, mantener el valor original
                        $processedRouteParams[$key] = $value;
                    }
                } else {
                    // Si no es un marcador de posición, mantener el valor original
                    $processedRouteParams[$key] = $value;
                }
            }

            // Actualizar los parámetros de ruta en el ítem
            $item['route_parameters'] = $processedRouteParams;
        }

        // Si es un array, resolver recursivamente cada elemento
        if (is_array($item)) {
            return $this->isAssociativeArray($item)
                ? $this->resolveAssociativeArray($item, $config, $routeParams)
                : $this->resolveSequentialArray(
                    array_values($item),
                    $config,
                    $routeParams
                );
        }

        // Para cualquier otro tipo, devolver tal como está
        return $item;
    }

    /**
     * Método de compatibilidad para buildBreadcrumbsFromContextual
     *
     * @param  array<int, array<string, mixed>>  $contextualItems
     * @return array<int, array{title: string, href: string}>
     */
    public function buildBreadcrumbsFromContextual(
        array $contextualItems,
        string $moduleSlug
    ): array {
        // Usar la lógica de la ruta actual para determinar el suffix
        $currentRoute = Route::currentRouteName();
        if (
            ! $currentRoute
            || ! str_starts_with($currentRoute, sprintf('internal.%s.', $moduleSlug))
        ) {
            return [
                [
                    'title' => ucfirst($moduleSlug),
                    'href' => $this->generateRoute(
                        sprintf('internal.%s.panel', $moduleSlug)
                    ),
                ],
            ];
        }

        $routeSuffix = mb_substr(
            $currentRoute,
            mb_strlen(sprintf('internal.%s.', $moduleSlug))
        );

        // Intentar usar la configuración si existe
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        /** @var array<string, mixed> $moduleConfig */
        $moduleConfigArr = $moduleConfig;
        if (
            isset($moduleConfigArr['breadcrumbs'])
            && is_array($moduleConfigArr['breadcrumbs'])
            && isset($moduleConfigArr['breadcrumbs'][$routeSuffix])
        ) {
            return $this->buildConfiguredBreadcrumbs($moduleSlug, $routeSuffix);
        }

        // Construir breadcrumbs a partir de los ítems contextuales (implementación básica)
        $breadcrumbs = [];

        // Agregar el primer ítem como principal
        if (
            $contextualItems !== []
            && isset($contextualItems[0])
        ) {
            $firstItem = $contextualItems[0];

            $firstTitle = isset($firstItem['title']) && is_string(
                $firstItem['title']
            ) ? $firstItem['title'] : ucfirst($moduleSlug);

            $firstHref = isset($firstItem['href']) && is_string(
                $firstItem['href']
            ) ? $firstItem['href'] : '#';

            $breadcrumbs[] = [
                'title' => $firstTitle,
                'href' => $firstHref,
            ];

            // Buscar el ítem activo como segundo nivel
            foreach ($contextualItems as $item) {
                $item = (array) $item;

                $isCurrent = isset($item['current'])
                    && $item['current'] === true;

                $itemTitle = isset($item['title']) && is_string(
                    $item['title']
                ) ? $item['title'] : '';

                if ($isCurrent && ($firstTitle !== $itemTitle)) {
                    $breadcrumbs[] = [
                        'title' => $itemTitle,
                        'href' => (isset($item['href']) && is_string(
                            $item['href']
                        )) ? $item['href'] : '#',
                    ];
                    break;
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Método de compatibilidad para buildGlobalNavItems
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return array<int, array<string, mixed>>
     */
    public function buildGlobalNavItems(
        array $itemsConfig,
        callable $permissionChecker
    ): array {
        // Procesar cada ítem global
        $items = [];

        foreach ($itemsConfig as $config) {
            $permission = $config['permission'] ?? null;

            if ($permission && ! $permissionChecker($permission)) {
                continue;
            }

            // Crear el ítem base
            $item = [
                'title' => isset($config['title']) && is_string($config['title'])
                    ? $config['title'] : '',
                'icon' => isset($config['icon']) && is_string($config['icon'])
                    ? $config['icon'] : null,
                'permission' => $permission,
            ];

            // Determinar la URL: usar href directo si está presente, de lo contrario generar ruta
            if (isset($config['href'])) {
                $href = is_string($config['href']) ? $config['href'] : '#';
                $item['href'] = $href;
                $item['current'] = $this->isCurrentUrl($href);
            } elseif (isset($config['route_name']) && is_string($config['route_name'])) {
                $routeName = $config['route_name'];

                $routeParameters = (array) ($config['route_params']
                    ?? $config['route_parameters']
                    ?? []);
                $routeParameters = $this->normalizeRouteParameters($routeParameters);

                $item['href'] = $this->generateRoute(
                    $routeName,
                    $routeParameters
                );

                $item['current'] = $this->isCurrentRoute($routeName);
            } else {
                $item['href'] = '#';
                $item['current'] = false;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Ensambla la estructura de navegación completa para una vista.
     *
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  string|null  $moduleSlug  Slug del módulo actual (null para dashboard principal)
     * @param  array<int, array<string, mixed>>  $contextualItemsConfig  Configuración de ítems contextuales
     * @param  mixed  $user  Usuario autenticado
     * @param  string|null  $functionalName  Nombre funcional del módulo
     * @param  string|null  $routeSuffix  Sufijo de ruta para breadcrumbs
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta para breadcrumbs
     * @param  array<string, mixed>  $viewData  Datos de la vista para títulos dinámicos
     * @return array<string, mixed> Estructura de navegación completa
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
    ): array {
        // Obtener los módulos habilitados
        $modules = $this->moduleRegistry->getAccessibleModules(
            $user instanceof StaffUsers ? $user : null
        );

        // Construir los ítems de navegación principal
        $mainNavItems = $this->buildNavItems($modules, $permissionChecker);

        // Construir los ítems de navegación de módulos
        $moduleNavItems = $this->buildModuleNavItems(
            $modules,
            $permissionChecker
        );

        // Construir los ítems de navegación contextual
        $contextualNavItems = [];
        if ($contextualItemsConfig !== []) {
            $contextualNavItems = $this->buildContextualNavItems(
                $contextualItemsConfig,
                $permissionChecker,
                $moduleSlug ?? '',
                $functionalName
            );
        }

        // Construir los ítems de navegación global (solo para el dashboard principal)
        $globalNavItems = [];
        if ($moduleSlug === null) {
            $globalItemsConfig = $this->moduleRegistry
                ->getGlobalNavItems($user instanceof StaffUsers ? $user : null);
            $globalNavItems = $this->buildGlobalNavItems(
                $globalItemsConfig,
                $permissionChecker
            );
        }

        // Construir breadcrumbs
        $breadcrumbs = [];
        if ($moduleSlug && $routeSuffix) {
            $breadcrumbs = $this->buildConfiguredBreadcrumbs(
                $moduleSlug,
                $routeSuffix,
                $routeParams,
                $viewData
            );
        } elseif ($contextualNavItems !== []) {
            $breadcrumbs = $this->buildBreadcrumbsFromContextual(
                $contextualNavItems,
                $moduleSlug ?? ''
            );
        }

        return [
            'mainNavItems' => $mainNavItems,
            'moduleNavItems' => $moduleNavItems,
            'contextualNavItems' => $contextualNavItems,
            'globalNavItems' => $globalNavItems,
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    /**
     * Método genérico para construir ítems (de navegación o de panel).
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @param  array<string, string>  $extraFields
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName,
        string $textKey,
        string $textTemplateKey,
        array $extraFields
    ): array {
        $builtItems = [];

        foreach ($itemsConfig as $config) {
            // Validación previa según tipo de item
            $errors = [];
            if ($textKey === 'name') {
                $errors = PanelItem::validate($config);
            } elseif ($textKey === 'title') {
                $errors = ContextualNavItem::validate($config);
            }

            if ($errors !== []) {
                Log::warning(
                    'Configuración de item inválida',
                    [
                        'module' => $moduleSlug,
                        'errors' => $errors,
                        'config' => $config,
                    ]
                );

                // Ignorar ítems inválidos para no romper la vista
                continue;
            }

            $permission = $config['permission'] ?? null;
            // Soporte para string o array de permisos (permite si el usuario tiene CUALQUIERA de los permisos proporcionados)
            if ($permission) {
                $allowed = true;
                if (is_array($permission)) {
                    $allowed = array_any($permission, fn ($perm): bool => is_string($perm) && $permissionChecker($perm));
                } elseif (is_string($permission)) {
                    $allowed = $permissionChecker($permission);
                }

                if (! $allowed) {
                    continue;
                }
            }

            // Determinar el texto a mostrar
            $text = isset($config[$textKey]) && is_string($config[$textKey])
                ? $config[$textKey]
                : null;
            if (
                isset($config[$textTemplateKey])
                && is_string($config[$textTemplateKey])
                && $functionalName
            ) {
                $text = sprintf($config[$textTemplateKey], $functionalName);
            }

            // Construir la ruta (preferir route_name si está presente, si no, usar route_name_suffix)
            $routeName = $config['route_name'] ?? null;
            if (! $routeName) {
                $routeNameSuffix = isset($config['route_name_suffix'])
                    && is_string($config['route_name_suffix'])
                    ? $config['route_name_suffix']
                    : null;
                $routeName = in_array($routeNameSuffix, [null, '', '0'], true)
                    ? 'internal.'.$moduleSlug
                    : sprintf('internal.%s.%s', $moduleSlug, $routeNameSuffix);
            }

            // Obtener los parámetros de ruta (preferir 'route_params' alias si existe)
            $routeParameters = [];
            if (
                isset($config['route_params'])
                && is_array($config['route_params'])
            ) {
                $routeParameters = $config['route_params'];
            } elseif (
                isset($config['route_parameters'])
                && is_array($config['route_parameters'])
            ) {
                $routeParameters = $config['route_parameters'];
            }

            // Crear el ítem base
            $item = [
                $textKey => $text,
                'icon' => isset($config['icon'])
                    && is_string($config['icon']) ? $config['icon'] : null,
                'permission' => $permission,
            ];

            // Agregar campos extra según el tipo
            foreach ($extraFields as $fieldKey => $configKey) {
                $item[$fieldKey] = match ($configKey) {
                    'route' => $this->generateRoute(
                        is_string($routeName) ? $routeName : '',
                        $this->normalizeRouteParameters($routeParameters)
                    ),
                    'current' => $this->isCurrentRoute(
                        is_string($routeName) ? $routeName : ''
                    ),
                    'route_name' => is_string($routeName) ? $routeName : '',
                    default => $config[$configKey] ?? null,
                };
            }

            $builtItems[] = $item;
        }

        return $builtItems;
    }

    /**
     * Genera una URL de ruta de forma segura.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function generateRoute(
        string $routeName,
        array $parameters = []
    ): string {
        try {
            if (Route::has($routeName)) {
                // Verificar si la ruta requiere parámetros
                $route = Route::getRoutes()->getByName($routeName);
                if ($route) {
                    $paramNames = [];
                    // Extraer nombres de parámetros de la URI
                    preg_match_all('/\{([^\}]+)\}/', $route->uri(), $matches);
                    $paramNames = $matches[1];

                    // Verificar si faltan parámetros requeridos
                    $missingParams = [];
                    foreach ($paramNames as $param) {
                        // Ignorar parámetros opcionales (con ?)
                        if (
                            ! str_contains($param, '?')
                            && ! isset($parameters[$param])
                        ) {
                            $missingParams[] = $param;
                        }
                    }

                    // Si faltan parámetros, no generar la ruta
                    if ($missingParams !== []) {
                        return '#';
                    }
                }

                return route($routeName, $parameters);
            }
        } catch (Exception $exception) {
            // Solo registrar errores críticos en producción
            if (app()->environment('production')) {
                Log::error(
                    'Error al generar ruta '.$routeName,
                    [
                        'exception' => $exception::class,
                        'route_name' => $routeName,
                        'parameters' => $parameters,
                    ]
                );
            }
        }

        return '#';
    }

    /**
     * Extrae un valor dinámico de los datos de la vista usando notación de punto.
     *
     * @param  string  $path  Ruta al valor (formato: 'usuario.name')
     * @param  array<string, mixed>  $data  Datos de donde extraer el valor
     * @return string|null El valor extraído o null si no se encuentra
     */
    private function extractDynamicTitle(string $path, array $data): ?string
    {
        $parts = explode('.', $path);
        $value = $data;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Resuelve referencias en un array asociativo.
     *
     * @param  array<string|int, mixed>  $array  Array asociativo a resolver
     * @param  array<string, mixed>  $config  Configuración completa
     * @param  array<string, mixed>  $routeParams  Parámetros adicionales para las rutas
     * @return array<string|int, mixed> Array con referencias resueltas
     */
    private function resolveAssociativeArray(
        array $array,
        array $config,
        array $routeParams = []
    ): array {
        $result = [];

        foreach ($array as $key => $value) {
            $resolvedValue = $this->resolveConfigReferences(
                $value,
                $config,
                $routeParams
            );

            // Si no es una clave string y el valor es un array secuencial, aplanarlo
            if (
                ! is_string($key) && is_array($resolvedValue)
                && ! $this->isAssociativeArray($resolvedValue)
            ) {
                foreach ($resolvedValue as $subValue) {
                    $result[] = $subValue;
                }
            } else {
                $result[$key] = $resolvedValue;
            }
        }

        return $result;
    }

    /**
     * Resuelve referencias en un array secuencial (numérico).
     *
     * @param  array<int, mixed>  $array  Array secuencial a resolver
     * @param  array<string, mixed>  $config  Configuración completa
     * @param  array<string, mixed>  $routeParams  Parámetros adicionales para las rutas
     * @return array<int, mixed> Array con referencias resueltas
     */
    private function resolveSequentialArray(
        array $array,
        array $config,
        array $routeParams = []
    ): array {
        $result = [];

        foreach ($array as $value) {
            $resolvedValue = $this->resolveConfigReferences(
                $value,
                $config,
                $routeParams
            );

            // Si el valor es un array secuencial, aplanarlo en el resultado
            if (
                is_array($resolvedValue)
                && ! $this->isAssociativeArray($resolvedValue)
            ) {
                foreach ($resolvedValue as $subValue) {
                    $result[] = $subValue;
                }
            } else {
                $result[] = $resolvedValue;
            }
        }

        return $result;
    }

    /**
     * Determina si un array es asociativo.
     *
     * @param  array<int|string, mixed>  $array
     */
    private function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Verifica si la URL actual coincide con la dada.
     */
    private function isCurrentUrl(string $url): bool
    {
        if ($url === '#') {
            return false;
        }

        $currentUrl = url()->current();

        return $currentUrl === $url;
    }

    /**
     * Normaliza parámetros de ruta, manteniendo únicamente claves string.
     *
     * @param  array<mixed>  $params
     * @return array<string, mixed>
     */
    private function normalizeRouteParameters(array $params): array
    {
        $normalized = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
