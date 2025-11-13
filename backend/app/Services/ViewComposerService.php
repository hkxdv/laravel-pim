<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\ViewComposerInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Servicio para componer y preparar datos para las vistas.
 * Encapsula la lógica de preparación de datos que se pasarán a los componentes de Inertia.
 */
final readonly class ViewComposerService implements ViewComposerInterface
{
    /**
     * Constructor del servicio de composición de vistas.
     */
    public function __construct(
        private NavigationBuilderInterface $navigationService,
        private ModuleRegistryInterface $moduleRegistry,
    ) {}

    /**
     * Prepara los datos comunes para las vistas del módulo.
     * Incluye panelItems, stats, pageTitle, description y mensajes flash.
     *
     * @param  array<int, array<string, mixed>>|array<string, mixed>  $panelItemsConfig
     * @param  array<string, mixed>|null  $stats
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareModuleViewData(
        string $moduleSlug,
        array $panelItemsConfig,
        callable $permissionChecker,
        string $functionalName,
        ?array $stats = null,
        array $data = []
    ): array {
        $isList = array_is_list($panelItemsConfig);

        $normalizedPanelItemsConfig = $isList
            ? $panelItemsConfig
            : [$panelItemsConfig];

        // Asegurar que cada ítem tenga llaves string para cumplir el contrato
        $normalizedPanelItemsConfig = array_map(
            static function ($item): array {
                if (! is_array($item)) {
                    return [];
                }

                $assoc = [];
                foreach ($item as $k => $v) {
                    $assoc[(string) $k] = $v;
                }

                return $assoc;
            },
            $normalizedPanelItemsConfig
        );

        $panelItems = $this->navigationService
            ->buildPanelItems(
                itemsConfig: $normalizedPanelItemsConfig,
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                functionalName: $functionalName
            );

        // Obtener descripción desde el config del módulo para exponerla como prop uniforme
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        $moduleDescription = $moduleConfig['description'] ?? null;

        return [
            ...[
                'panelItems' => $panelItems,
                'stats' => (object) ($stats ?? []),
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    /**
     * Renderiza una vista de Inertia con los datos del módulo.
     *
     * @param  array<string, mixed>  $data
     */
    public function renderModuleView(
        string $view,
        string $moduleViewPath,
        array $data = []
    ): InertiaResponse {
        return Inertia::render(sprintf('modules/%s/%s', $moduleViewPath, $view), $data);
    }

    /**
     * Obtiene los mensajes flash de la sesión.
     *
     * @return array<string, mixed>
     */
    public function getFlashMessages(Request $request): array
    {
        return [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
            'info' => $request->session()->get('info'),
            'warning' => $request->session()->get('warning'),
            'credentials' => $request->session()->get('credentials'),
        ];
    }

    /**
     * Método mejorado que prepara todos los datos necesarios para una vista de módulo en un solo paso.
     * Encapsula la complejidad de obtener navegación, breadcrumbs, etc.
     * Retorna props estándar: panelItems, mainNavItems, moduleNavItems, contextualNavItems,
     * globalNavItems, breadcrumbs, stats, pageTitle, description y flash.
     *
     * @param  array<int, array<string, mixed>>  $panelItemsConfig
     * @param  array<int, array<string, mixed>>  $contextualNavItemsConfig
     * @param  mixed  $user
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $stats
     * @param  string|null  $routeSuffix  Sufijo de ruta para los breadcrumbs configurados
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta para los breadcrumbs
     * @return array<string, mixed>
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
    ): array {
        // Normalizar nombre funcional y obtener descripción desde config del módulo
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        $fn = $moduleConfig['functional_name'] ?? null;
        $functionalName = is_string($functionalName)
            ? $functionalName
            : (is_string($fn) ? $fn : ucfirst($moduleSlug));
        $moduleDescription = $moduleConfig['description'] ?? null;

        // Obtener todos los elementos de navegación
        $navigationElements = $this->navigationService
            ->assembleNavigationStructure(
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                contextualItemsConfig: $contextualNavItemsConfig,
                user: $user,
                functionalName: $functionalName,
                routeSuffix: $routeSuffix,
                routeParams: $routeParams,
                viewData: $data
            );

        // Construir ítems del panel
        $panelItems = $this->navigationService
            ->buildNavigation(
                navType: NavigationBuilderInterface::NAV_TYPE_PANEL,
                itemsConfig: $panelItemsConfig,
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                functionalName: $functionalName
            );

        // Combinar todos los datos
        return [
            ...[
                'panelItems' => $panelItems,
                'mainNavItems' => $navigationElements['mainNavItems'] ?? [],
                'moduleNavItems' => $navigationElements['moduleNavItems'] ?? [],
                'contextualNavItems' => $navigationElements['contextualNavItems'],
                'globalNavItems' => $navigationElements['globalNavItems'],
                'breadcrumbs' => $navigationElements['breadcrumbs'],
                'stats' => (object) ($stats ?? []),
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    /**
     * Método específico para preparar datos del dashboard principal.
     *
     * @param  mixed  $user
     * @param  array<int, mixed>  $availableModules
     * @return array<string, mixed>
     */
    public function composeDashboardViewContext(
        $user,
        array $availableModules,
        callable $permissionChecker,
        Request $request
    ): array {
        // Obtener elementos de navegación usando el servicio de navegación
        $navigationElements = $this->navigationService
            ->assembleNavigationStructure(
                permissionChecker: $permissionChecker,
                moduleSlug: null,
                contextualItemsConfig: [],
                user: $user
            );

        // Obtener todos los módulos habilitados (para mostrar también los restringidos)
        $allModules = $this->moduleRegistry->getAllEnabledModules();

        // Preparar tarjetas de módulos y navegación contextual (combinadas con canAccess)
        $typedAvailableModules = array_values(
            array_filter(
                $availableModules,
                static fn ($m): bool => $m instanceof \Nwidart\Modules\Laravel\Module
            )
        );

        $moduleCards = $this->navigationService
            ->buildModuleCards(
                $allModules,
                $typedAvailableModules
            );

        // Separar backend en accesibles y restringidos para simplificar el frontend
        $accessibleCards = [];
        $restrictedCards = [];
        foreach ($moduleCards as $card) {
            if (! empty($card['canAccess'])) {
                $accessibleCards[] = $card;
            } else {
                $restrictedCards[] = $card;
            }
        }

        // Definir props de encabezado para el dashboard
        $pageTitle = __('Panel de control');
        $description = __('Seleccione un módulo para acceder a sus funciones.');

        // Breadcrumbs del dashboard principal
        $breadcrumbs = [
            [
                'title' => __('Dashboard'),
                'href' => route('internal.dashboard'),
            ],
        ];

        return [
            // Para compatibilidad previa: arreglo combinado
            'modules' => $moduleCards,
            'accessibleModules' => $accessibleCards,
            'restrictedModules' => $restrictedCards,
            'mainNavItems' => $navigationElements['mainNavItems'] ?? [],
            'moduleNavItems' => $navigationElements['moduleNavItems'] ?? [],
            'contextualNavItems' => $navigationElements['contextualNavItems'],
            'globalNavItems' => $navigationElements['globalNavItems'],
            'breadcrumbs' => $breadcrumbs,
            'pageTitle' => $pageTitle,
            'description' => $description,
            'flash' => $this->getFlashMessages($request),
        ];
    }
}
