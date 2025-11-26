<?php

declare(strict_types=1);

use Modules\Assistant\App\Mcp\Servers\AgentOpsServer;
use Modules\Assistant\App\Mcp\Tools\SearchProductTool;

it(
    'accepts q arg as alias for search',
    function () {
        $response = AgentOpsServer::tool(SearchProductTool::class, [
            'q' => 'Samsung',
            'is_active' => true,
            'per_page' => 3,
        ]);

        $response->assertOk();
    }
);
