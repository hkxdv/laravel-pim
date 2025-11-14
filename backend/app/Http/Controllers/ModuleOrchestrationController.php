<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\ViewComposerInterface;
use App\Traits\PermissionVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;
use ReflectionClass;
use Throwable;

/**
 * Controlador base para todos los módulos del sistema.
 *
 * Este controlador implementa el patrón Template Method para orquestar la renderización
 * de vistas de módulos, proporcionando una estructura común y puntos de extensión bien definidos.
 *
 * Responsabilidades:
 * - Autodetectar el slug y configuración del módulo desde el namespace
 * - Orquestar la renderización de vistas Inertia con contexto completo
 * - Gestionar navegación contextual y breadcrumbs dinámicos
 * - Proporcionar verificación de permisos y autenticación por guard
 *
 * Patrón de extensión:
 * Los controladores hijos NO deben sobrescribir métodos finales. En su lugar, deben
 * implementar los siguientes métodos protegidos para personalizar el comportamiento:
 *
 * - getModuleStats(): Define estadísticas específicas del módulo
 * - getAdditionalPanelData(): Añade datos adicionales al panel principal
 * - getPanelItemsConfig(): Personaliza los items del panel (opcional)
 * - getContextualNavItemsConfig(): Personaliza la navegación contextual (opcional)
 */
abstract class ModuleOrchestrationController extends Controller
{
    use PermissionVerifier;

    /**
     * Clave identificadora del módulo (slug), autodetectada del namespace.
     */
    protected string $moduleSlug = '';

    /**
     * Configuración del módulo cargada desde el archivo de configuración.
     *
     * @var array<string, mixed>
     */
    protected array $moduleConfig = [];

    /**
     * Constructor del controlador base para módulos.
     *
     * @param  ModuleRegistryInterface  $moduleRegistryService  Servicio para gestionar el registro y acceso a los módulos.
     * @param  ViewComposerInterface  $viewComposerService  Servicio para componer y preparar los datos para las vistas.
     * @param  NavigationBuilderInterface|null  $navigationService  Servicio para construir la navegación (opcional).
     */
    public function __construct(
        protected readonly ModuleRegistryInterface $moduleRegistryService,
        protected readonly ViewComposerInterface $viewComposerService,
        protected readonly ?NavigationBuilderInterface $navigationService = null
    ) {
        // Autodetectar slug y cargar configuración del módulo basado en el namespace.
        $this->detectModuleAndLoadConfig();
    }

    /**
     * Muestra el panel principal del módulo.
     *
     * Este método está marcado como FINAL y NO debe ser sobrescrito por las clases hijas.
     * Implementa el patrón Template Method para renderizar el panel del módulo de forma consistente.
     *
     * Flujo de ejecución:
     * 1. Obtiene las estadísticas del módulo mediante getModuleStats()
     * 2. Obtiene datos adicionales mediante getAdditionalPanelData()
     * 3. Combina todos los datos y renderiza la vista 'index' del módulo
     *
     * Para personalizar el panel, los controladores hijos deben implementar:
     * - getModuleStats(): para proveer estadísticas específicas
     * - getAdditionalPanelData(): para añadir datos personalizados (e.g., actividad reciente)
     *
     * @param  Request  $request  Solicitud HTTP entrante
     * @return InertiaResponse Respuesta Inertia con la vista del panel y sus datos
     *
     * @see getModuleStats() Método de extensión para estadísticas
     * @see getAdditionalPanelData() Método de extensión para datos adicionales
     */
    final public function showModulePanel(Request $request): InertiaResponse
    {
        $additional = ['stats' => $this->getModuleStats()];

        $extras = $this->getAdditionalPanelData();

        if ($extras !== []) {
            $additional = array_merge($additional, $extras);
        }

        return $this->prepareAndRenderModuleView(
            view: 'index',
            request: $request,
            additionalData: $additional
        );
    }

    /**
     * Obtiene el slug del módulo.
     *
     * El slug es autodetectado desde el namespace del controlador durante la construcción.
     * Por ejemplo: Modules\Admin\... genera el slug 'admin'.
     *
     * @return string Slug del módulo en minúsculas (e.g., 'admin', 'module01')
     */
    protected function getModuleSlug(): string
    {
        return $this->moduleSlug;
    }

    /**
     * Obtiene el nombre funcional del módulo desde la configuración.
     *
     * Este nombre es utilizado como título principal en las vistas del módulo.
     *
     * @return string Nombre funcional del módulo para mostrar al usuario
     */
    protected function getFunctionalName(): string
    {
        $name = $this->moduleConfig['functional_name'] ?? '';

        return is_string($name) ? $name : '';
    }

    /**
     * Obtiene el directorio de vistas de Inertia para este módulo.
     *
     * Este directorio se usa para resolver las rutas de componentes React/Vue en el frontend.
     *
     * @return string Ruta del directorio de vistas (por defecto usa el slug del módulo)
     */
    protected function getInertiaViewDirectory(): string
    {
        $dir = $this->moduleConfig['inertia_view_directory']
            ?? $this->moduleSlug;

        return is_string($dir) ? $dir : $this->moduleSlug;
    }

    /**
     * Obtiene el/los permisos base requeridos para acceder al módulo.
     *
     * @return array<int, string>|string
     */
    protected function getBaseAccessPermission(): string|array
    {
        $perm = $this->moduleConfig['base_permission'] ?? '';

        if (is_string($perm)) {
            return $perm;
        }

        if (is_array($perm)) {
            // Normalizar a lista de strings
            return array_values(array_filter($perm, is_string(...)));
        }

        return '';
    }

    /**
     * Obtiene el guard de autenticación configurado para este módulo.
     *
     * Ejemplos comunes: 'staff', 'web', 'api'
     *
     * @return string Nombre del guard de autenticación
     */
    protected function getAuthGuard(): string
    {
        $guard = $this->moduleConfig['auth_guard'] ?? '';

        return is_string($guard) ? $guard : '';
    }

    /**
     * Devuelve el usuario autenticado usando el guard especificado.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getAuthenticatedUser()
    {
        return Auth::guard($this->getAuthGuard())->user();
    }

    /**
     * Obtiene la configuración de los ítems del panel principal del módulo.
     * Implementación por defecto que lee desde la configuración del módulo.
     * Los controladores hijos pueden sobrescribir si necesitan lógica adicional.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getPanelItemsConfig(): array
    {
        $panelConfig = $this->moduleConfig['panel_items'] ?? [];
        if (! is_array($panelConfig)) {
            return [];
        }

        // Asegurar lista de ítems de panel con cada ítem como array
        $normalized = [];
        foreach ($panelConfig as $item) {
            if (is_array($item)) {
                // Forzar claves de string para cumplir array<string, mixed>
                $normalizedItem = [];
                foreach ($item as $k => $v) {
                    $normalizedItem[(string) $k] = $v;
                }

                /** @var array<string, mixed> $normalizedItem */
                $normalized[] = $normalizedItem;
            }
        }

        /** @var array<int, array<string, mixed>> $normalized */
        return $normalized;
    }

    /**
     * Obtiene la configuración de los ítems de navegación contextual para el módulo.
     * Implementación por defecto que intenta:
     * 1) Detectar dinámicamente el sufijo de la ruta actual y usar esa clave si existe.
     * 2) En caso de no existir una clave específica, usar la clave 'default' como fallback.
     * Los controladores hijos pueden sobrescribir si necesitan lógica adicional específica.
     *
     * Nota: Devuelve una lista que puede contener referencias en forma de strings, que serán resueltas posteriormente.
     *
     * @return array<int, mixed>
     */
    protected function getContextualNavItemsConfig(): array
    {
        $navConfigAll = $this->moduleConfig['contextual_nav'] ?? [];

        if (! is_array($navConfigAll)) {
            return [];
        }

        // Intentar usar configuración específica por la ruta actual
        try {
            $currentRequest = request();
            // request() siempre retorna instancia en contexto web; evitar && redundante
            if ($currentRequest->route()) {
                $suffix = $this->extractRouteSuffixFromRequest($currentRequest);
                if (
                    isset($navConfigAll[$suffix])
                    && is_array($navConfigAll[$suffix])
                ) {
                    // Mantener referencias ($ref:...) para que se resuelvan más adelante
                    return array_values($navConfigAll[$suffix]);
                }
            }
        } catch (Throwable) {
            return [];
        }

        // Fallback a 'default' si está disponible
        if (
            isset($navConfigAll['default'])
            && is_array($navConfigAll['default'])
        ) {
            return array_values($navConfigAll['default']);
        }

        return [];
    }

    /**
     * Define las estadísticas para el panel del módulo.
     *
     * MÉTODO DE EXTENSIÓN: Los controladores hijos deben sobrescribir este método
     * para proporcionar estadísticas específicas del módulo.
     *
     * Las estadísticas se mostrarán en el panel principal del módulo y pueden incluir
     * métricas como: total de registros, usuarios activos, transacciones del día, etc.
     *
     * Ejemplo de implementación:
     * ```php
     * protected function getModuleStats(): ?array
     * {
     *     return $this->statsService->getPanelStats(
     *         $this->getModuleSlug(),
     *         $this->getAuthenticatedUser()
     *     );
     * }
     * ```
     *
     * @return array<int, \App\DTO\EnhancedStat>|null Array de estadísticas o null si no aplica
     */
    protected function getModuleStats(): ?array
    {
        return null; // Por defecto no hay estadísticas
    }

    /**
     * Permite a cada módulo aportar datos adicionales para el panel.
     *
     * MÉTODO DE EXTENSIÓN: Los controladores hijos pueden sobrescribir este método
     * para agregar datos personalizados que se pasarán a la vista del panel.
     *
     * Los datos retornados se fusionarán con las estadísticas del módulo y estarán
     * disponibles en el componente React/Vue del panel.
     *
     * Casos de uso comunes:
     * - Actividad reciente del sistema
     * - Notificaciones o alertas
     * - Datos contextuales específicos del módulo
     * - Listas de accesos rápidos personalizados
     *
     * Ejemplo de implementación:
     * ```php
     * protected function getAdditionalPanelData(): array
     * {
     *     return [
     *         'recentActivity' => $this->getRecentActivity(),
     *         'pendingTasks' => $this->getPendingTasks(),
     *     ];
     * }
     * ```
     *
     * @return array<string, mixed> Array asociativo con datos adicionales (vacío por defecto)
     */
    protected function getAdditionalPanelData(): array
    {
        return [];
    }

    /**
     * Renderiza una vista de Inertia específica del módulo con datos comunes.
     *
     * Este método utiliza el ViewComposerService para construir la ruta completa
     * de la vista Inertia y renderizarla con los datos proporcionados.
     *
     * @param  string  $view  Nombre de la vista a renderizar (e.g., 'index', 'create', 'edit')
     * @param  array<string, mixed>  $data  Datos a pasar a la vista
     * @return InertiaResponse Respuesta Inertia con la vista renderizada
     *
     * @example
     * ```php
     * return $this->renderModuleView('index', [
     *     'stats' => $this->getModuleStats(),
     * ]);
     * ```
     */
    protected function renderModuleView(
        string $view,
        array $data = []
    ): InertiaResponse {
        // Renderizar la vista usando el servicio, que se encarga de construir la ruta correcta.
        return $this->viewComposerService->renderModuleView(
            view: $view,
            moduleViewPath: $this->getInertiaViewDirectory(),
            data: $data
        );
    }

    /**
     * Resuelve referencias en la configuración del módulo.
     *
     * Utiliza el servicio de navegación para resolver referencias del tipo '$ref:path.to.component'
     * y reemplazar placeholders dinámicos con valores de parámetros de ruta.
     *
     * Ejemplo de referencia: '$ref:breadcrumbs.users.edit' se resolverá al valor
     * configurado en el path 'breadcrumbs.users.edit' de la configuración del módulo.
     *
     * @param  mixed  $config  Configuración que puede contener referencias
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta actuales para resolver placeholders dinámicos
     * @return mixed Configuración con referencias resueltas
     */
    protected function resolveConfigReferences(mixed $config, array $routeParams = []): mixed
    {
        if ($this->navigationService && (! in_array($config, [null, '', []], true))) {
            // Forward route params to allow dynamic resolution in breadcrumbs and nav
            return $this->navigationService->resolveConfigReferences(
                $config,
                $this->moduleConfig
            );
        }

        return $config;
    }

    /**
     * Prepara el contexto completo para una vista de módulo y la renderiza.
     * Este método encapsula el patrón común de preparación y renderizado de vistas.
     * Siempre incluye pageTitle (functional_name), description (desde config) y breadcrumbs.
     *
     * @param  string  $view  Nombre de la vista a renderizar.
     * @param  Request  $request  Request actual para obtener el usuario y permisos.
     * @param  array<string, mixed>  $additionalData  Datos adicionales específicos de la vista.
     * @param  array<int, array<string, mixed>>|null  $customPanelItems  Items personalizados del panel (opcional).
     * @param  array<int, array<string, mixed>>|null  $customNavItems  Items personalizados de navegación contextual (opcional).
     * @param  string|null  $routeSuffix  Sufijo de la ruta actual (opcional, se detectará automáticamente si es null).
     * @param  array<string, mixed>  $routeParams  Parámetros de la ruta para los breadcrumbs (opcional).
     * @param  array<string, mixed>  $dynamicTitleData  Datos específicos para títulos dinámicos en breadcrumbs (opcional).
     * @return InertiaResponse Respuesta Inertia con el contexto completo.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado (403).
     *
     * @example
     * ```php
     * return $this->prepareAndRenderModuleView(
     *     view: 'orders/list',
     *     request: $request,
     *     additionalData: ['orders' => $orders, 'filters' => $filters],
     *     routeSuffix: 'orders.list'
     * );
     * ```
     */
    protected function prepareAndRenderModuleView(
        string $view,
        Request $request,
        array $additionalData = [],
        ?array $customPanelItems = null,
        ?array $customNavItems = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $dynamicTitleData = []
    ): InertiaResponse {
        // Obtener el usuario y verificar autenticación
        $user = $request->user($this->getAuthGuard())
            ?: abort(403, 'Usuario no autenticado');

        // Si no se proporcionaron parámetros de ruta, intentar obtenerlos de la solicitud actual
        if ($routeParams === []) {
            $route = $request->route();
            $routeParams = $route ? $route->parameters() : [];
        }

        // Normalizar route params a array<string, mixed>
        $normalizedRouteParams = [];
        foreach ($routeParams as $key => $value) {
            $normalizedRouteParams[(string) $key] = $value;
        }

        $routeParams = $normalizedRouteParams;

        // Obtener la configuración de navegación del módulo
        $panelItemsConfig = $customPanelItems
            ?? $this->getPanelItemsConfig();
        $contextualNavItemsConfig = $customNavItems
            ?? $this->getContextualNavItemsConfig();

        // Resolver referencias en las configuraciones
        if ($this->navigationService instanceof NavigationBuilderInterface) {
            $panelItemsConfig = $this->resolveConfigReferences(
                $panelItemsConfig,
                $routeParams
            );
            $contextualNavItemsConfig = $this->resolveConfigReferences(
                $contextualNavItemsConfig,
                $routeParams
            );
        }

        // Re-normalizar configuraciones tras resolver referencias
        /** @var array<int, array<string, mixed>> $panelItemsConfig */
        $panelItemsConfig = is_array($panelItemsConfig)
            ? array_values(array_filter($panelItemsConfig, is_array(...)))
            : [];
        /** @var array<int, array<string, mixed>> $contextualNavItemsConfig */
        $contextualNavItemsConfig = is_array($contextualNavItemsConfig)
            ? array_values(array_filter($contextualNavItemsConfig, is_array(...)))
            : [];
        $functionalName = $this->getFunctionalName();

        // Determinar el sufijo de ruta si no se proporcionó
        $routeSuffix ??= $this->extractRouteSuffixFromRequest($request);

        // Combinar datos de título dinámico con datos adicionales
        $viewData = array_merge($additionalData, $dynamicTitleData);

        // Preparar el contexto de la vista usando el servicio
        /** @var array<string, mixed>|null $statsParam */
        $statsParam = null;
        if (
            isset($additionalData['stats'])
            && is_array($additionalData['stats'])
        ) {
            $statsParam = [];
            foreach ($additionalData['stats'] as $k => $v) {
                $statsParam[(string) $k] = $v;
            }
        }

        $viewContext = $this->viewComposerService
            ->composeModuleViewContext(
                moduleSlug: $this->moduleSlug,
                panelItemsConfig: $panelItemsConfig,
                contextualNavItemsConfig: $contextualNavItemsConfig,
                permissionChecker: $this->can(...),
                user: $user,
                functionalName: $functionalName,
                data: $viewData,
                stats: $statsParam,
                routeSuffix: $routeSuffix,
                routeParams: $routeParams
            );

        return $this->renderModuleView($view, $viewContext);
    }

    /**
     * Detecta el slug del módulo desde el namespace y carga su configuración.
     *
     * Este método analiza el namespace de la clase hija para extraer el nombre del módulo
     * y luego carga su configuración desde el sistema de registro de módulos.
     *
     * Convención: Modules\NombreModulo\... -> slug: 'nombremodulo'
     *
     * Se ejecuta automáticamente en el constructor.
     */
    private function detectModuleAndLoadConfig(): void
    {
        // Obtiene el namespace completo de la clase hija que está siendo instanciada.
        $namespace = new ReflectionClass(static::class)->getNamespaceName();

        // Divide el namespace en partes para extraer el nombre del módulo.
        $parts = explode('\\', $namespace);

        // El nombre del módulo debe ser el segundo elemento (índice 1).
        // e.g., Modules\Admin\Http\Controllers -> 'Admin'
        if ($parts[0] === 'Modules' && isset($parts[1])) {
            // El slug del módulo es el segundo elemento, en minúsculas.
            $this->moduleSlug = mb_strtolower($parts[1]);
            $this->moduleConfig = $this->moduleRegistryService
                ->getModuleConfig($this->moduleSlug);
        }
    }

    /**
     * Extrae el sufijo de ruta del objeto Request.
     *
     * Analiza el nombre de la ruta actual para extraer el sufijo después del prefijo del módulo.
     * Por ejemplo: 'internal.admin.users.create' -> 'users.create'
     *
     * Este sufijo se utiliza para determinar qué navegación contextual mostrar.
     *
     * @param  Request  $request  Request actual para analizar
     * @return string Sufijo de la ruta (por defecto: 'último segmento' o 'panel')
     */
    private function extractRouteSuffixFromRequest(Request $request): string
    {
        $route = $request->route();
        $currentRoute = $route ? $route->getName() : null;

        // Si la ruta actual pertenece al módulo, extraer el sufijo
        if (
            $currentRoute && str_starts_with(
                $currentRoute,
                sprintf('internal.%s.', $this->moduleSlug)
            )
        ) {
            return mb_substr(
                $currentRoute,
                mb_strlen(sprintf('internal.%s.', $this->moduleSlug))
            );
        }

        // Por defecto, usar 'panel' o el último segmento de la ruta actual
        $parts = explode('.', $currentRoute ?? '');

        return end($parts) ?: 'panel';
    }
}
