<?php

declare(strict_types=1);

namespace Modules\Sales\App\Http\Controllers;

use App\Http\Controllers\ModuleOrchestrationController;
use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\StatsServiceInterface;
use App\Interfaces\ViewComposerInterface;

abstract class SalesBaseController extends ModuleOrchestrationController
{
    /**
     * @param  ModuleRegistryInterface  $moduleRegistryService  Servicio para el registro de módulos.
     * @param  ViewComposerInterface  $viewComposerService  Servicio para componer vistas.
     * @param  NavigationBuilderInterface  $navigationBuilderService  Servicio para construir la navegación.
     */
    public function __construct(
        ModuleRegistryInterface $moduleRegistryService,
        ViewComposerInterface $viewComposerService,
        protected readonly NavigationBuilderInterface $navigationBuilderService,
        protected readonly StatsServiceInterface $statsService,
    ) {
        parent::__construct(
            moduleRegistryService: $moduleRegistryService,
            viewComposerService: $viewComposerService,
            navigationService: $navigationBuilderService
        );
    }
}
