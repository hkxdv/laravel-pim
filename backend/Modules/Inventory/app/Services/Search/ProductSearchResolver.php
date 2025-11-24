<?php

declare(strict_types=1);

namespace Modules\Inventory\App\Services\Search;

use Illuminate\Support\Facades\Config;
use Modules\Inventory\App\Interfaces\ProductSearchInterface;

final class ProductSearchResolver
{
    public static function resolve(): ProductSearchInterface
    {
        return match (self::currentMode()) {
            'typesense' => new TypesenseProductSearchService(),
            default => new SqliteProductSearchService(),
        };
    }

    public static function currentMode(): string
    {
        $envMode = getenv('SEARCH_MODE') ?: null;
        $modeRaw = $envMode ?? \Illuminate\Support\Env::get('SEARCH_MODE');
        $mode = is_string($modeRaw) ? mb_trim($modeRaw) : '';

        if ($mode === '') {
            $driverRaw = Config::get('scout.driver', '');
            $driver = is_string($driverRaw)
                ? mb_strtolower(mb_trim($driverRaw)) : '';

            return $driver === 'typesense' ? 'typesense' : 'sqlite';
        }

        $normalized = mb_strtolower($mode);

        return $normalized === 'typesense' ? 'typesense' : 'sqlite';
    }
}
