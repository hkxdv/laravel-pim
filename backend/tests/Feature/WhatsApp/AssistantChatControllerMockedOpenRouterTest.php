<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Modules\Assistant\App\Events\McpToolDebug;
use Modules\Assistant\App\Http\Controllers\AssistantChatController;
use Modules\Inventory\App\Models\Product;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

app()->instance(HttpClientInterface::class, new MockHttpClient());

it(
    'maps OpenRouter decision to price_for_user and logs MCP tool traces',
    function (): void {
        Product::query()->create([
            'sku' => 'IP14',
            'name' => 'iPhone 14',
            'price' => 999.00,
            'stock' => 5,
            'is_active' => true,
        ]);
        Config::set('services.openrouter.provider', 'openrouter');
        Config::set('services.openrouter.model', 'meta-llama/llama-3.2-3b-instruct');
        Config::set('services.openrouter.api_key', 'test');
        Config::set('services.openrouter.max_tokens', 64);
        Config::set('services.openrouter.referrer', '');
        Config::set('services.openrouter.title', 'PIM Assistant');

        $content = json_encode([
            'choices' => [[
                'message' => [
                    'content' => '{
                        "tool":"price_for_user",
                        "args": {
                            "sku":"IP14",
                            "quantity":2,
                            "multiplier":"0.9",
                            "round":true
                        }
                    }',
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $mockResponse = new MockResponse(
            $content ?: '{}',
            ['http_code' => 200]
        );
        $mockClient = new MockHttpClient($mockResponse);
        app()->instance(HttpClientInterface::class, $mockClient);

        Event::fake([McpToolDebug::class]);

        $controller = new AssistantChatController();
        $request = Request::create(
            '/api/assistant',
            'POST',
            ['text' => 'Precio SKU: IP14 cantidad: 2 mult: 0.9']
        );
        $response = $controller->__invoke($request);

        /** @var array<string, mixed> $data */
        $data = $response->getData(true);
        expect($data['status'] ?? null)->toBe('ok');
        expect(
            ($data['decision'] ?? [])['tool'] ?? null
        )->toBe('price_for_user');
        expect(
            ((array) (($data['result'] ?? [])))['raw'] ?? null
        )->toBeNull();
        expect(($data['result'] ?? null))->toBeArray();

        Event::assertDispatched(
            McpToolDebug::class,
            function (McpToolDebug $event): bool {
                return $event->tool === 'PriceForUserTool'
                    && $event->phase === 'start';
            }
        );

        Event::assertDispatched(
            McpToolDebug::class,
            function (McpToolDebug $event): bool {
                return $event->tool === 'PriceForUserTool'
                    && $event->phase === 'end'
                    && isset($event->data['subtotal']);
            }
        );
    }
);
