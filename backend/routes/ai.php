<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use Modules\Assistant\App\Mcp\Servers\AgentOpsServer;

Mcp::web('/mcp/agent-ops', AgentOpsServer::class)
    ->middleware(['auth:sanctum', 'abilities:basic', 'throttle:60,1']);

Mcp::local('agent-ops', AgentOpsServer::class);
