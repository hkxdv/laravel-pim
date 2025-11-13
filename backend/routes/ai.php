<?php

declare(strict_types=1);

use App\Mcp\Servers\AgentOpsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/agent-ops', AgentOpsServer::class)
    ->middleware(['throttle:mcp']);

Mcp::local('agent-ops', AgentOpsServer::class);
