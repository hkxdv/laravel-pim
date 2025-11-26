<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Http\Controllers;

use App\Http\Controllers\ModuleOrchestrationController;
use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\StatsServiceInterface;
use App\Interfaces\ViewComposerInterface;
use Modules\Inventory\App\Interfaces\InventoryManagerInterface;

/**
 * Controlador base para todos los controladores del Módulo.
 * Proporciona la estructura común y la inyección de dependencias necesarias para el módulo.
 */
abstract class InventoryBaseController extends ModuleOrchestrationController
{
    /**
     * Constructor del controlador base del Módulo.
     *
     * @param  ModuleRegistryInterface  $moduleRegistryService  Servicio para el registro de módulos.
     * @param  ViewComposerInterface  $viewComposerService  Servicio para componer vistas.
     * @param  NavigationBuilderInterface  $navigationBuilderService  Servicio para construir la navegación.
     */
    public function __construct(
        // Dependencias para el controlador padre (ModuleOrchestrationController)
        ModuleRegistryInterface $moduleRegistryService,
        ViewComposerInterface $viewComposerService,
        // Dependencias para este controlador y sus hijos
        protected readonly NavigationBuilderInterface $navigationBuilderService,
        protected readonly InventoryManagerInterface $inventoryManager,
        protected readonly StatsServiceInterface $statsService,
    ) {
        parent::__construct(
            moduleRegistryService: $moduleRegistryService,
            viewComposerService: $viewComposerService,
            navigationService: $navigationBuilderService
        );
    }
}
