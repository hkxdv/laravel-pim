<?php

declare(strict_types=1);

use Modules\Module01\App\Services\Search\ProductSearchResolver;

it('returns sqlite mode by default outside container', function () {
    putenv('SEARCH_MODE=');
    $_ENV['SEARCH_MODE'] = '';
    $_SERVER['SEARCH_MODE'] = '';
    putenv('APP_RUNNING_IN_CONTAINER=false');
    $_ENV['APP_RUNNING_IN_CONTAINER'] = 'false';
    $_SERVER['APP_RUNNING_IN_CONTAINER'] = 'false';

    $mode = ProductSearchResolver::currentMode();
    expect($mode)->toBe('sqlite');
});

it('returns typesense mode when SEARCH_MODE=typesense', function () {
    putenv('SEARCH_MODE=typesense');
    $_ENV['SEARCH_MODE'] = 'typesense';
    $_SERVER['SEARCH_MODE'] = 'typesense';

    $mode = ProductSearchResolver::currentMode();
    expect($mode)->toBe('typesense');
});
