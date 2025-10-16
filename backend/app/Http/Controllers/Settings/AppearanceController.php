<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Inertia;
use Inertia\Response;

final class AppearanceController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración de apariencia.
     */
    public function show(): Response
    {
        return Inertia::render('settings/appearance', [
            'contextualNavItems' => $this->getSettingsNavigationItems(),
        ]);
    }
}
