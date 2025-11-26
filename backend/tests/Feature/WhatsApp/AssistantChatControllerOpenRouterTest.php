<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\Assistant\App\Http\Controllers\AssistantChatController;

it(
    'queries OpenRouter and returns a decision when API key present',
    function (): void {
        $apiKey = env('OPENROUTER_API_KEY', '');
        if (! is_string($apiKey) || $apiKey === '') {
            test()->markTestSkipped('OPENROUTER_API_KEY not configured');
        }

        Config::set('services.openrouter.provider', 'openrouter');
        Config::set('services.openrouter.model', 'meta-llama/llama-3.2-3b-instruct');
        Config::set('services.openrouter.api_key', $apiKey);
        Config::set('services.openrouter.max_tokens', 128);
        Config::set('services.openrouter.referrer', '');
        Config::set('services.openrouter.title', 'PIM Assistant');

        $controller = new AssistantChatController();
        $request = Request::create(
            '/api/assistant',
            'POST',
            ['text' => 'Centro Carga Samsung A03s']
        );
        $response = $controller->__invoke($request);

        /** @var array<string, mixed> $data */
        $data = $response->getData(true);

        expect($data['status'] ?? null)->toBe('ok');
        expect($data['llm'] ?? null)->not->toBeNull();
        $tool = (string) (
            ($data['decision'] ?? [])['tool'] ?? ''
        );
        expect($tool)->not->toBe('');
        expect(
            ['search_product', 'price_for_user', 'create_pre_order']
        )->toContain($tool);
        expect(
            ($data['decision'] ?? [])['args'] ?? null
        )->toBeArray();
    }
);
