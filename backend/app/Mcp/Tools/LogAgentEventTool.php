<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\AgentLog;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class LogAgentEventTool extends Tool
{
    /**
     * Descripción del tool para el LLM.
     */
    protected string $description = 'Registra un evento del agente en la tabla agent_logs con acción, estado y metadatos.';

    /**
     * Esquema JSON de entrada para validar los argumentos.
     */
    public function inputSchema(): JsonSchema
    {
        return JsonSchema::object([
            'action' => JsonSchema::string()->description(
                'Acción realizada por el agente (ej: "busqueda", "navegacion", "pedido").'
            ),
            'status' => JsonSchema::string()->description(
                'Estado del evento (ok, error, pending)'
            )->default('ok'),
            'payload' => JsonSchema::object()->description(
                'Payload recibido o parámetros usados'
            )->nullable(),
            'meta' => JsonSchema::object()->description(
                'Metadatos adicionales para trazabilidad'
            )->nullable(),
        ]);
    }

    /**
     * Manejo del tool: crea el registro en agent_logs y devuelve el id.
     */
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'min:1'],
            'status' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        /** @var array{action: string, status?: string|null, payload?: array<string, mixed>|null, meta?: array<string, mixed>|null} $data */
        $startNs = hrtime(true);

        $action = $data['action'];
        $status = $data['status'] ?? 'ok';
        $payload = $data['payload'] ?? null;
        $meta = $data['meta'] ?? [];

        $userAgent = request()->userAgent() ?? 'unknown';

        $log = AgentLog::query()->create([
            'agent_name' => 'mcp',
            'user_id' => null,
            'module' => 'Module03',
            'action' => $action,
            'status' => $status,
            'duration_ms' => 0, // se actualiza abajo
            'request_payload' => $payload,
            'response_payload' => null,
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
        ]);

        $durationMs = (int) floor((hrtime(true) - $startNs) / 1_000_000);
        $log->update(['duration_ms' => $durationMs]);

        return Response::json([
            'status' => 'ok',
            'log_id' => $log->id,
            'duration_ms' => $durationMs,
        ]);
    }
}
