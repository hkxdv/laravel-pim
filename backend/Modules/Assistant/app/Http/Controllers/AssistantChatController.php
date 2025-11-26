<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response as McpResponse;
use Modules\Assistant\App\Mcp\Tools\CreatePreOrderTool;
use Modules\Assistant\App\Mcp\Tools\PriceForUserTool;
use Modules\Assistant\App\Mcp\Tools\SearchProductTool;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class AssistantChatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $text = (string) $request->string('text');
        $providerCfg = Config::get('services.openrouter.provider', '');
        $provider = is_string($providerCfg) ? $providerCfg : '';
        $modelCfg = Config::get('services.openrouter.model', '');
        $model = is_string($modelCfg) ? $modelCfg : '';
        $llmUsed = false;

        $toolDecision = [
            'tool' => 'search_product',
            'args' => [
                'search' => $text,
                'is_active' => true,
                'per_page' => 5,
            ],
        ];

        try {
            if ($text !== '') {
                $system = <<<'TXT'
Eres un asistente de inventario. Devuelve SOLO un objeto JSON con el esquema exacto:
{"tool":"search_product|price_for_user|create_pre_order","args":{"...": "..."}}
Reglas:
- Usa claves en inglés: search, sku, quantity, multiplier, round, ignore_inactive, per_page, is_active.
- Para texto libre, elige "search_product" con args: {"search":"<término>","is_active":true,"per_page":5}.
- Para precio, elige "price_for_user" con args: {"sku":"<sku>","quantity":<int>,"multiplier":"0.9","round":true}.
- Para pedido preliminar, elige "create_pre_order" con args: {"items":{"<SKU>":<int>,...},"multiplier":"0.9","round":true,"ignore_inactive":true}.
- No traduzcas valores ni términos del usuario en args; conserva el idioma original.
- Usa SIEMPRE uno de los nombres de herramienta listados; no inventes nombres ni claves.
- Si el mensaje contiene "tienes", "disponible" o "stock", prioriza "search_product".
- NO incluyas texto fuera del JSON. Si tienes dudas, usa "search_product".
Ejemplos:
{"tool":"search_product","args":{"search":"Batería iPhone 14","is_active":true,"per_page":5}}
{"tool":"search_product","args":{"search":"Samsung A03s","is_active":true,"per_page":5}}
{"tool":"price_for_user","args":{"sku":"GS23","quantity":2,"multiplier":"0.9","round":true}}
{"tool":"create_pre_order","args":{"items":{"GS23":1,"IP14":2},"multiplier":"0.9","round":true,"ignore_inactive":true}}
TXT;

                $payload = [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $text],
                    ],
                    'temperature' => 0,
                    'max_tokens' => (
                        is_numeric(
                            Config::get('services.openrouter.max_tokens', 256)
                        ) ? (int) Config::get('services.openrouter.max_tokens', 256) : 256
                    ),
                ];

                $decision = null;
                $llmUsed = false;

                $client = $this->getHttpClient();
                $apiKey = Config::get('services.openrouter.api_key', '');
                $headers = [
                    'Authorization' => 'Bearer '.(is_string($apiKey) ? $apiKey : ''),
                    'Content-Type' => 'application/json',
                ];

                $refCfg = Config::get('services.openrouter.referrer', '');
                $titleCfg = Config::get('services.openrouter.title', '');
                $ref = is_string($refCfg) ? $refCfg : '';
                $title = is_string($titleCfg) ? $titleCfg : '';
                if ($ref !== '') {
                    $headers['HTTP-Referer'] = $ref;
                }

                if ($title !== '') {
                    $headers['X-Title'] = $title;
                }

                $resp = $client->request(
                    'POST',
                    'https://openrouter.ai/api/v1/chat/completions',
                    [
                        'headers' => $headers,
                        'json' => $payload + ['response_format' => ['type' => 'json_object']],
                    ]
                );

                $data = $resp->toArray(false);
                $content = '';
                $choices = $data['choices'] ?? null;
                if (is_array($choices) && isset($choices[0]) && is_array($choices[0])) {
                    $message = $choices[0]['message'] ?? null;
                    if (is_array($message)) {
                        $contentRaw = $message['content'] ?? '';
                        $content = is_string($contentRaw) ? $contentRaw : '';
                    }
                }

                $decoded = json_decode($content, true);
                if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $content, $mm) === 1) {
                    $decoded = json_decode($mm[0], true);
                }

                if (
                    is_array($decoded)
                    && is_string($decoded['tool'] ?? null)
                    && is_array($decoded['args'] ?? null)
                ) {
                    $toolDecision = [
                        'tool' => $decoded['tool'],
                        'args' => $decoded['args'],
                    ];
                    $llmUsed = true;
                }
            }
        } catch (Throwable $throwable) {
            Log::warning(
                'LLM decision failed, falling back to search_product',
                [
                    'error' => $throwable->getMessage(),
                    'provider' => $provider,
                    'model' => $model,
                ]
            );
        }

        $toolMap = [
            'search_product' => SearchProductTool::class,
            'price_for_user' => PriceForUserTool::class,
            'create_pre_order' => CreatePreOrderTool::class,
        ];

        $toolName = $toolDecision['tool'];
        $args = $toolDecision['args'];
        if (isset($args['q']) && is_string($args['q'])) {
            $searchVal = $args['search'] ?? null;
            if (! is_string($searchVal) || $searchVal === '') {
                $args['search'] = $args['q'];
                unset($args['q']);
            }
        }

        $toolClass = $toolMap[$toolName] ?? SearchProductTool::class;

        try {
            if ($toolName === 'search_product') {
                $s = is_string($args['search'] ?? null) ? $args['search'] : '';
                $args['search'] = $this->normalizeSearchArg($s, $text);
            }

            $toolInstance = app()->make($toolClass);
            $reqArgs = [];
            foreach ($args as $k => $v) {
                $reqArgs[(string) $k] = $v;
            }

            $response = app()->call([$toolInstance, 'handle'], [
                'request' => new McpRequest($reqArgs),
            ]);

            $rawContent = $response instanceof McpResponse ? $response->content() : '';
            $raw = is_string($rawContent) ? $rawContent : (string) $rawContent;
            $parsed = json_decode($raw, true);

            $summary = '';
            if ($toolName === 'search_product') {
                $summary = is_array($parsed) ? '' : $raw;
                if ($summary === '') {
                    $summary = 'Consulta realizada. Se muestran los resultados disponibles.';
                }
            }

            return response()->json([
                'status' => 'ok',
                'llm' => $llmUsed ? [
                    'provider' => $provider,
                    'model' => $model,
                ] : null,
                'decision' => [
                    'tool' => $toolName,
                    'args' => $args,
                ],
                'result' => is_array($parsed) ? $parsed : ['raw' => $raw],
                'summary' => $summary,
            ]);
        } catch (Throwable $throwable) {
            Log::error('Assistant tool execution failed', [
                'tool' => $toolClass,
                'args' => $args,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'assistant_failed',
            ], 500);
        }
    }

    private function getHttpClient(): HttpClientInterface
    {
        if (! app()->bound(HttpClientInterface::class)) {
            return HttpClient::create();
        }

        return app()->make(HttpClientInterface::class);
    }

    private function normalizeSearchArg(string $s, string $fallback): string
    {
        if (preg_match('/SKU[:\s]*([A-Za-z0-9\-]+)/iu', $fallback, $m) === 1) {
            return $m[1];
        }

        $x = $s !== '' ? $s : $fallback;
        $x = preg_replace('/^\s*(tienes|hay|consulta|consultar|quiero|puedes|por\s+favor|disponible)\b\s*/iu', '', $x) ?? $x;
        $x = preg_replace('/\bmarca[:\s]*/iu', '', $x) ?? $x;
        $x = preg_replace('/\bmodelo[:\s]*/iu', '', $x) ?? $x;
        $x = preg_replace('/\s{2,}/u', ' ', $x) ?? $x;

        return mb_trim($x);
    }
}
