<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Transport\FakeTransporter;
use Modules\Assistant\App\Mcp\Prompts\DescribeProductPrompt;
use Modules\Assistant\App\Mcp\Servers\AgentOpsServer;
use Modules\Assistant\App\Mcp\Tools\CreatePreOrderTool;
use Modules\Assistant\App\Mcp\Tools\LogAgentEventTool;
use Modules\Assistant\App\Mcp\Tools\PriceForUserTool;
use Modules\Assistant\App\Mcp\Tools\SearchProductTool;

it(
    'registers MCP tools and prompts',
    function (): void {
        $server = new AgentOpsServer(new FakeTransporter());
        $ref = new ReflectionClass($server);

        $nameProp = $ref->getProperty('name');
        $versionProp = $ref->getProperty('version');
        $instructionsProp = $ref->getProperty('instructions');
        $toolsProp = $ref->getProperty('tools');
        $promptsProp = $ref->getProperty('prompts');

        /** @var array<int, class-string> $tools */
        $tools = $toolsProp->getValue($server);
        /** @var array<int, class-string> $prompts */
        $prompts = $promptsProp->getValue($server);

        expect($nameProp->getValue($server))->toBe('Agent Ops Server');
        expect($versionProp->getValue($server))->toBe('1.0.0');
        expect($instructionsProp->getValue($server))->toBeString();

        expect($tools)->toContain(
            LogAgentEventTool::class,
            SearchProductTool::class,
            PriceForUserTool::class,
            CreatePreOrderTool::class,
        );

        expect($prompts)->toContain(DescribeProductPrompt::class);
    }
);
