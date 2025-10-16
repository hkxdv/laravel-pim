<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ModuleRegistryService;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador base para las páginas de configuración.
 * Proporciona funcionalidades compartidas, como la construcción del menú de navegación.
 */
abstract class BaseSettingsController extends Controller
{
    public function __construct(protected ModuleRegistryService $moduleRegistry) {}

    /**
     * Obtiene los ítems de navegación para el menú de configuración.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getSettingsNavigationItems(): array
    {
        return $this->moduleRegistry->getGlobalNavItems(Auth::user());
    }
}
