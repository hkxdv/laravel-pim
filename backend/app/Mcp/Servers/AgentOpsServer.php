<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Prompts\DescribirProductoPrompt;
use App\Mcp\Tools\BuscarProductoTool;
use App\Mcp\Tools\CrearPedidoPreliminarTool;
use App\Mcp\Tools\LogAgentEventTool;
use App\Mcp\Tools\PrecioParaUsuarioTool;
use Laravel\Mcp\Server;

final class AgentOpsServer extends Server
{
    /**
     * Nombre del servidor MCP.
     */
    protected string $name = 'Agent Ops Server';

    /**
     * Versión del servidor MCP.
     */
    protected string $version = '1.0.0';

    /**
     * Instrucciones para el LLM que interactúa con este servidor.
     */
    protected string $instructions = 'Servidor MCP para registrar eventos del agente y operar con inventario de forma acotada.';

    /**
     * Herramientas registradas en este servidor MCP.
     *
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        LogAgentEventTool::class,
        BuscarProductoTool::class,
        PrecioParaUsuarioTool::class,
        CrearPedidoPreliminarTool::class,
    ];

    /**
     * Recursos y prompts pueden agregarse posteriormente según el plan.
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<Server\Prompt>>
     */
    protected array $prompts = [
        DescribirProductoPrompt::class,
    ];
}
