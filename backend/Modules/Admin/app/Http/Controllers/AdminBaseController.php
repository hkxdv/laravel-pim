<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\ModuleOrchestrationController;
use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\StatsServiceInterface;
use App\Interfaces\ViewComposerInterface;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;

/**
 * Controlador base para todos los controladores del Módulo Admin.
 * Proporciona la estructura común y la inyección de dependencias necesarias para el módulo.
 */
abstract class AdminBaseController extends ModuleOrchestrationController
{
    /**
     * Constructor del controlador base del Módulo Admin.
     *
     * Utiliza la promoción de propiedades de PHP 8 para inyectar las dependencias
     * y las pasa correctamente al controlador padre (ModuleOrchestrationController).
     *
     * @param  ModuleRegistryInterface  $moduleRegistryService  Servicio para el registro de módulos.
     * @param  ViewComposerInterface  $viewComposerService  Servicio para componer vistas.
     * @param  NavigationBuilderInterface  $navigationBuilderService  Servicio para construir la navegación.
     * @param  StaffUserManagerInterface  $staffUserManager  Servicio para gestionar usuarios del staff.
     */
    public function __construct(
        // Dependencias para el controlador padre
        ModuleRegistryInterface $moduleRegistryService,
        ViewComposerInterface $viewComposerService,
        // Dependencias para este controlador y sus hijos
        protected readonly NavigationBuilderInterface $navigationBuilderService,
        protected readonly StaffUserManagerInterface $staffUserManager,
        protected readonly StatsServiceInterface $statsService,
    ) {
        // Llama al constructor del padre con las dependencias correctas.
        parent::__construct(
            moduleRegistryService: $moduleRegistryService,
            viewComposerService: $viewComposerService,
            navigationService: $navigationBuilderService
        );
    }
}
