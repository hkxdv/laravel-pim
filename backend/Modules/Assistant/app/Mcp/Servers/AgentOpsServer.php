<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Mcp\Servers;

use Laravel\Mcp\Server;
use Modules\Assistant\App\Mcp\Prompts\DescribeProductPrompt;
use Modules\Assistant\App\Mcp\Tools\CreatePreOrderTool;
use Modules\Assistant\App\Mcp\Tools\LogAgentEventTool;
use Modules\Assistant\App\Mcp\Tools\PriceForUserTool;
use Modules\Assistant\App\Mcp\Tools\SearchProductTool;

final class AgentOpsServer extends Server
{
    protected string $name = 'Agent Ops Server';

    protected string $version = '1.0.0';

    protected string $instructions = 'Servidor MCP para registrar eventos del agente y operar con inventario de forma acotada.';

    /** @var array<int, class-string<Server\Tool>> */
    protected array $tools = [
        LogAgentEventTool::class,
        SearchProductTool::class,
        PriceForUserTool::class,
        CreatePreOrderTool::class,
    ];

    /** @var array<int, class-string<Server\Prompt>> */
    protected array $prompts = [
        DescribeProductPrompt::class,
    ];
}
