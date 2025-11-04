<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AgentLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class RequestHeadersAuditMiddleware
{
    /** @var array<string,bool> */
    private array $redacted = [
        'authorization' => true,
        'cookie' => true,
        'x-xsrf-token' => true,
        'x-csrf-token' => true,
        'set-cookie' => true,
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Auditar cabeceras entrantes
        $headersRaw = $request->headers->all();
        $headers = [];
        foreach ($headersRaw as $name => $values) {
            // Symfony HeaderBag::all() returns array<string, array<string>>
            $value = implode(', ', $values);
            $headers[$name] = $this->redacted[mb_strtolower($name)] ?? false
                ? '***redacted***'
                : $value;
        }

        // Información básica de la solicitud
        $context = [
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'controller' => $request->route()?->getActionName(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $headers,
        ];

        // Registrar en logs de aplicación
        Log::info('HTTP request audit', $context);

        // Capturar X-Search-Mode si está presente y almacenar en AgentLog
        $xSearchMode = $request->headers->get('X-Search-Mode');
        if ($xSearchMode !== null && $xSearchMode !== '') {
            try {
                AgentLog::query()->create([
                    'action' => 'request',
                    'agent_name' => 'http-audit',
                    'module' => $this->inferModuleFromPath($request->path()),
                    'status' => 'ok',
                    'request_payload' => [
                        'method' => $request->getMethod(),
                        'url' => $request->fullUrl(),
                        'headers' => $headers,
                    ],
                    'response_payload' => null,
                    'meta' => [
                        'x_search_mode' => $xSearchMode,
                    ],
                    'user_id' => $request->user()?->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (Throwable $e) {
                // Si no existe la tabla o hay error de BD, no bloquear la solicitud
                Log::warning('HTTP audit: failed to persist X-Search-Mode', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $next($request);
    }

    private function inferModuleFromPath(string $path): ?string
    {
        // Intenta detectar "module-01" del path y normalizar a "Module01"
        if (preg_match('/module-(\d+)/', $path, $m)) {
            $num = $m[1];

            return 'Module'.mb_str_pad($num, 2, '0', STR_PAD_LEFT);
        }

        return null;
    }
}
