<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Services\Ai;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

final class SummaryService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{text: string, usage: array<string, mixed>|null, provider: ?string, model: ?string}
     */
    public function summarize(array $payload, string $intent): array
    {
        $enabledRaw = Config::get('services.ai.enabled', 'false');
        $enabled = filter_var(is_string($enabledRaw) ? $enabledRaw : 'false', FILTER_VALIDATE_BOOL);
        if (! $enabled) {
            return [
                'text' => '',
                'usage' => null,
                'provider' => null,
                'model' => null,
            ];
        }

        $provider = 'openrouter';
        $modelRaw = Config::get('services.openrouter.model', 'meta-llama/llama-3.2-3b-instruct');
        $model = is_string($modelRaw) ? $modelRaw : 'meta-llama/llama-3.2-3b-instruct';
        $maxTokensRaw = Config::get('services.openrouter.max_tokens', 256);
        $maxTokens = is_numeric($maxTokensRaw) ? (int) $maxTokensRaw : 256;

        try {
            $client = HttpClient::create();
            $apiKeyRaw = Config::get('services.openrouter.api_key', '');
            $headers = [
                'Authorization' => 'Bearer '.(is_string($apiKeyRaw) ? $apiKeyRaw : ''),
                'Content-Type' => 'application/json',
            ];
            $refRaw = Config::get('services.openrouter.referrer', '');
            $titleRaw = Config::get('services.openrouter.title', '');
            $ref = is_string($refRaw) ? $refRaw : '';
            $title = is_string($titleRaw) ? $titleRaw : '';
            if ($ref !== '') {
                $headers['HTTP-Referer'] = $ref;
            }

            if ($title !== '') {
                $headers['X-Title'] = $title;
            }

            $system = 'Redacta una respuesta breve (máx. 2 líneas) en español basada exclusivamente en los datos calculados.
- No inventes datos.
- No prometas disponibilidad ni precios si no están presentes.
- Usa un tono claro y útil.
- Si no hay resultados, sugiere brevemente cómo mejorar la búsqueda.';

            $userContent = json_encode([
                'intent' => $intent,
                'payload' => $payload,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $resp = $client->request(
                'POST',
                'https://openrouter.ai/api/v1/chat/completions',
                [
                    'headers' => $headers,
                    'json' => [
                        'model' => $model,
                        'response_format' => ['type' => 'json_object'],
                        'max_tokens' => $maxTokens,
                        'temperature' => 0,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $system,
                            ],
                            [
                                'role' => 'user',
                                'content' => (string) $userContent,
                            ],
                        ],
                    ],
                ]
            );

            $data = $resp->toArray(false);
            /** @var array<string, mixed> $data */
            $content = '';
            $choices = $data['choices'] ?? null;
            if (is_array($choices) && isset($choices[0]) && is_array($choices[0])) {
                $message = $choices[0]['message'] ?? null;
                if (is_array($message)) {
                    $contentRaw = $message['content'] ?? '';
                    $content = is_string($contentRaw) ? $contentRaw : '';
                }
            }

            $text = $content;
            $usageRaw = $data['usage'] ?? null;
            $usage = null;
            if (is_array($usageRaw)) {
                $usage = [];
                foreach ($usageRaw as $k => $v) {
                    $usage[(string) $k] = $v;
                }
            }

            return [
                'text' => $text,
                'usage' => $usage,
                'provider' => $provider,
                'model' => $model,
            ];
        } catch (Throwable $throwable) {
            Log::warning('AI summary failed', [
                'error' => $throwable->getMessage(),
                'provider' => $provider,
                'model' => $model,
            ]);

            return [
                'text' => '',
                'usage' => null,
                'provider' => $provider,
                'model' => $model,
            ];
        }
    }
}
