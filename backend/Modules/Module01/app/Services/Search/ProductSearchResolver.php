<?php

declare(strict_types=1);

namespace Modules\Module01\App\Services\Search;

use Modules\Module01\App\Interfaces\ProductSearchInterface;

final class ProductSearchResolver
{
    public static function resolve(): ProductSearchInterface
    {
        $mode = env('SEARCH_MODE');
        if ($mode === null || $mode === '') {
            // Interpretar correctamente el flag de contenedor como booleano
            $runningInContainerRaw = env('APP_RUNNING_IN_CONTAINER');
            $runningInContainer = filter_var(
                $runningInContainerRaw,
                FILTER_VALIDATE_BOOL
            );

            $mode = $runningInContainer ? 'typesense' : 'sqlite';
        }

        return match (self::currentMode()) {
            'typesense' => new TypesenseProductSearchService(),
            default => new SqliteProductSearchService(),
        };
    }

    public static function currentMode(): string
    {
        $mode = env('SEARCH_MODE');
        if ($mode === null || $mode === '') {
            // Interpretar correctamente el flag de contenedor como booleano
            $runningInContainerRaw = env('APP_RUNNING_IN_CONTAINER');
            $runningInContainer = filter_var(
                $runningInContainerRaw,
                FILTER_VALIDATE_BOOL
            );

            $mode = $runningInContainer ? 'typesense' : 'sqlite';
        }

        $mode = mb_strtolower((string) $mode);

        return $mode === 'typesense' ? 'typesense' : 'sqlite';
    }
}
