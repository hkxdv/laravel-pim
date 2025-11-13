<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader as TranslationFileLoader;
use Illuminate\Translation\Translator as TranslationTranslator;

final class EarlyBindingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind temprano para 'cache' para evitar fallos en procesos de arranque/descubrimiento
        if (! $this->app->bound('cache')) {
            $this->app->singleton(
                'cache',
                fn (ApplicationContract $app): CacheManager => new CacheManager($app)
            );
        }

        // Asegurar driver de caché por defecto si no está configurado
        $config = app(ConfigRepository::class);
        if ($config->get('cache.default') === null) {
            $config->set('cache.default', 'array');
        }

        // Bind mínimo de 'translator' para prevenir errores durante el descubrimiento de paquetes
        if (! $this->app->bound('translator')) {
            $this->app->singleton(
                'translator',
                function (ApplicationContract $app): TranslationTranslator {
                    $langPath = base_path('resources/lang');
                    $loader = new TranslationFileLoader(
                        new Filesystem,
                        $langPath
                    );

                    $locale = 'en';
                    if ($app->has('config')) {
                        $localeValue = app(
                            ConfigRepository::class
                        )->get('app.locale');
                        $locale = is_string($localeValue)
                            ? $localeValue : 'en';
                    }

                    $translator = new TranslationTranslator($loader, $locale);

                    $fallback = 'en';
                    if ($app->has('config')) {
                        $fallbackValue = app(
                            ConfigRepository::class
                        )->get('app.fallback_locale');
                        $fallback = is_string($fallbackValue)
                            ? $fallbackValue : 'en';
                    }

                    $translator->setFallback($fallback);

                    return $translator;
                }
            );
        }
    }

    public function boot(): void
    {
        // Eloquent estricto y fail-fast
        Model::shouldBeStrict();
        Model::preventLazyLoading();
        Model::preventSilentlyDiscardingAttributes();
        Model::preventAccessingMissingAttributes();

        // Fechas inmutables globales
        Date::use(CarbonImmutable::class);
    }
}
