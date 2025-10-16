<?php

declare(strict_types=1);

namespace Modules\Module02\App\Http\Controllers;

use App\Http\Controllers\ModuleOrchestrationController;
use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\StatsServiceInterface;
use App\Interfaces\ViewComposerInterface;

/**
 * Controlador base para todos los controladores del Módulo 02.
 * Proporciona la estructura común y la inyección de dependencias necesarias para el módulo.
 */
abstract class Module02BaseController extends ModuleOrchestrationController
{
    /**
     * Constructor del controlador base del Módulo 02.
     *
     * Utiliza la promoción de propiedades de PHP 8 para inyectar las dependencias
     * y las pasa correctamente al controlador padre (ModuleOrchestrationController).
     *
     * @param  ModuleRegistryInterface  $moduleRegistryService  Servicio para el registro de módulos.
     * @param  ViewComposerInterface  $viewComposerService  Servicio para componer vistas.
     * @param  NavigationBuilderInterface  $navigationBuilderService  Servicio para construir la navegación.
     */
    public function __construct(
        // Dependencias para el controlador padre
        ModuleRegistryInterface $moduleRegistryService,
        ViewComposerInterface $viewComposerService,
        // Dependencias para este controlador y sus hijos
        protected readonly NavigationBuilderInterface $navigationBuilderService,
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
