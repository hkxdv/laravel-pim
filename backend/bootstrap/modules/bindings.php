<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;

return function (Application $application): void {
    // Bind temprano para 'cache' y 'translator' para evitar fallos en registro de paquetes
    try {
        if (! $application->bound('cache')) {
            $application->singleton('cache', fn(Application $app): \Illuminate\Cache\CacheManager => new \Illuminate\Cache\CacheManager($app));
        }

        if ($application->has('config')) {
            $config = $application->make(\Illuminate\Contracts\Config\Repository::class);
            if ($config->get('cache.default') === null) {
                $config->set('cache.default', 'array');
            }
        }
    } catch (\Throwable) {
        // Silenciar errores aquí para no bloquear el arranque; proveedores lo corregirán
    }

    try {
        if (! $application->bound('translator')) {
            $application->singleton(
                'translator',
                function (Application $app): \Illuminate\Translation\Translator {
                    $langPath = dirname(__DIR__, 1) . '/resources/lang';
                    $loader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem, $langPath);
                    $locale = 'en';
                    if ($app->has('config')) {
                        $localeValue = $app->make(\Illuminate\Contracts\Config\Repository::class)->get('app.locale');
                        $locale = is_string($localeValue) ? $localeValue : 'en';
                    }

                    $translator = new \Illuminate\Translation\Translator($loader, $locale);
                    $fallback = 'en';
                    if ($app->has('config')) {
                        $fallbackValue = $app->make(\Illuminate\Contracts\Config\Repository::class)->get('app.fallback_locale');
                        $fallback = is_string($fallbackValue) ? $fallbackValue : 'en';
                    }

                    $translator->setFallback($fallback);

                    return $translator;
                }
            );
        }
    } catch (\Throwable) {
        // Si falla, se cubrirá cuando TranslationServiceProvider se registre
    }
};
