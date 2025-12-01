<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Services\Search\ProductSearchResolver;
use Modules\WhatsApp\App\Models\WhatsAppSession;
use Modules\WhatsApp\App\Services\WhatsAppConversationService;
use Modules\WhatsApp\App\Services\WhatsAppTemplateService;

/**
 * Controlador principal para manejar los webhooks entrantes de Twilio (WhatsApp).
 * Orquesta el flujo de la conversación, pausas y búsquedas de inventario.
 */
final class TwilioWebhookController extends Controller
{
    /**
     * Maneja la solicitud entrante de Twilio.
     */
    public function handle(
        Request $request,
        WhatsAppConversationService $conversationService,
        WhatsAppTemplateService $templateService
    ): Response {
        hrtime(true);
        DB::enableQueryLog();

        $input = $this->parseRequest($request);

        Log::info('Twilio webhook received', [
            'from' => $input['from'],
            'body' => $input['body'],
            'payload' => $input['payload'],
        ]);

        $session = $conversationService->get($input['from']);

        // 1. Manejo de estado "Pausado" (Gate)
        if ($conversationService->isPaused($session)) {
            return $this->handlePausedState(
                $session,
                $input,
                $conversationService,
                $templateService
            );
        }

        // 2. Manejo de botones interactivos (Acciones)
        if ($input['is_interactive']) {
            $response = $this->handleInteractiveActions(
                $session,
                $input,
                $conversationService,
                $templateService
            );

            if ($response instanceof Response) {
                return $response;
            }
        }

        // 3. Verificar flujo de bienvenida
        if (! $this->ensureWelcomeShown(
            $session,
            $input['from'],
            $templateService,
            $conversationService
        )) {
            return $this->sendXmlResponse();
        }

        // 4. Ejecutar búsqueda si corresponde
        if ($session->search_enabled && $input['body'] !== '') {
            $this->performSearch(
                $input['body'],
                $input['from'],
                $templateService
            );
        }

        return $this->sendXmlResponse();
    }

    /**
     * Parsea y normaliza los datos de la solicitud.
     *
     * @return array{from: string, body: string, type: string, payload: string, is_interactive: bool}
     */
    private function parseRequest(Request $request): array
    {
        $from = $request->input('From');
        $body = $request->input('Body');
        $msgType = $request->input('MessageType');
        $payload = $request->input('ButtonPayload');

        $type = is_string($msgType) ? mb_strtolower($msgType) : '';

        return [
            'from' => is_string($from) ? $from : 'unknown',
            'body' => is_string($body) ? $body : '',
            'type' => $type,
            'payload' => is_string($payload)
                ? mb_strtoupper(mb_trim($payload)) : '',
            'is_interactive' => $type === 'interactive',
        ];
    }

    /**
     * Gestiona la lógica cuando la sesión está pausada.
     * Permite reanudar si se pulsa BTN_RESUME, de lo contrario mantiene el silencio.
     *
     * @param  array{from: string, body: string, type: string, payload: string, is_interactive: bool}  $input
     */
    private function handlePausedState(
        WhatsAppSession $session,
        array $input,
        WhatsAppConversationService $conversationService,
        WhatsAppTemplateService $templateService
    ): Response {
        $isBtnResume = $input['is_interactive']
            && $input['payload'] === 'BTN_RESUME';

        if ($isBtnResume) {
            $conversationService->unmute($session);

            $greet = config('services.whatsapp.gate_greeting', '');
            $greeting = (is_string($greet) && $greet !== '')
                ? $greet : 'Hola, soy el Bot asistente de inventario,';

            $templateService->sendWelcomePrompt(
                $input['from'],
                ['greeting' => $greeting]
            );

            return $this->sendXmlResponse();
        }

        // Si no es reanudar, verificamos si ya enviamos el recordatorio de pausa una vez
        $meta = is_array($session->meta) ? $session->meta : [];
        $sentOnce = (bool) ($meta['resume_sent_once'] ?? false);

        if (! $sentOnce) {
            $templateService->sendResumePrompt(
                $input['from'],
                ['greeting' => '']
            );

            $meta['resume_sent_once'] = true;
            $meta['resume_sent_at'] = Date::now()->toISOString();
            $session->meta = $meta;
            $session->save();
        }

        return $this->sendXmlResponse();
    }

    /**
     * Despacha acciones basadas en botones interactivos.
     *
     * @param  array{from: string, body: string, type: string, payload: string, is_interactive: bool}  $input
     */
    private function handleInteractiveActions(
        WhatsAppSession $session,
        array $input,
        WhatsAppConversationService $conversationService,
        WhatsAppTemplateService $templateService
    ): ?Response {
        $payload = $input['payload'];

        // Acciones de Pausa
        if ($payload === 'BTN_PAUSE') {
            $conversationService->pauseGate($session);
            $templateService->sendPauseOptionsPrompt($input['from']);

            return $this->sendXmlResponse();
        }

        if (in_array(
            $payload,
            ['BTN_PAUSE_1H', 'BTN_PAUSE_FOREVER', 'BTN_PAUSE_INDEFINITELY'],
            true
        )) {
            return $this->handlePauseSelection(
                $session,
                $input['from'],
                $payload,
                $conversationService,
                $templateService
            );
        }

        // Acciones de Búsqueda Genérica
        if (in_array(
            $payload,
            ['BTN_SEARCH', 'BTN_TRY_AGAIN', 'BTN_QUERY_AGAIN'],
            true
        )) {
            $conversationService->enableSearch($session);
            $templateService->sendQueryPrompt($input['from']);

            return $this->sendXmlResponse();
        }

        // Acción: Ver Ejemplos
        if ($payload === 'BTN_EXAMPLES') {
            $conversationService->enableSearch($session);
            // Usamos plantilla dinámica
            $templateService->sendDynamicPrompt(
                $input['from'],
                "Puedes buscar de varias formas:\n• Por nombre: *Funda*\n• Por modelo: *iPhone 15*\n• Por SKU: *IP15-CS*"
            );

            return $this->sendXmlResponse();
        }

        // Acción: Ayuda
        if ($payload === 'BTN_HELP') {
            $conversationService->enableSearch($session);
            // Usamos plantilla dinámica
            $templateService->sendDynamicPrompt(
                $input['from'],
                "Estoy aquí para ayudarte.\nSimplemente escribe qué producto necesitas y buscaré en nuestro inventario en tiempo real."
            );

            return $this->sendXmlResponse();
        }

        return null; // Continuar flujo normal
    }

    /**
     * Procesa la selección de opciones de pausa.
     */
    private function handlePauseSelection(
        WhatsAppSession $session,
        string $from,
        string $payload,
        WhatsAppConversationService $conversationService,
        WhatsAppTemplateService $templateService
    ): Response {
        if ($payload === 'BTN_PAUSE_FOREVER') {
            $conversationService->muteForever($session);
            $templateService->sendTextMessage(
                $from,
                'A partir de este momento no recibirás más mensajes automáticos. 😕'
            );
        } elseif ($payload === 'BTN_PAUSE_INDEFINITELY') {
            $conversationService->pauseGate($session);
            $templateService->sendResumePrompt(
                $from,
                ['greeting' => '']
            );
        } elseif ($payload === 'BTN_PAUSE_1H') {
            $conversationService->muteFor($session, 60, '1h');
            $templateService->sendTextMessage(
                $from,
                'Pausa temporal. El bot se silenciará durante 60 minutos.'
            );
        }

        return $this->sendXmlResponse();
    }

    /**
     * Asegura que se muestre el mensaje de bienvenida si no se ha visto antes.
     * Retorna true si ya fue mostrado (o se acaba de mostrar), false si el flujo debe detenerse.
     */
    private function ensureWelcomeShown(
        WhatsAppSession $session,
        string $from,
        WhatsAppTemplateService $templateService,
        WhatsAppConversationService $conversationService
    ): bool {
        $meta = is_array($session->meta) ? $session->meta : [];
        if ($meta['welcome_shown'] ?? false) {
            return true;
        }

        $greet = config('services.whatsapp.gate_greeting', '');
        $greeting = (is_string($greet) && $greet !== '')
            ? $greet : 'Hola, soy el Bot asistente de inventario,';

        $templateService->sendWelcomePrompt($from, ['greeting' => $greeting]);
        $conversationService->markWelcomeShown($session);

        return false; // Detener flujo para esperar interacción del usuario tras bienvenida
    }

    /**
     * Ejecuta la búsqueda de productos y envía resultados.
     */
    private function performSearch(
        string $query,
        string $from,
        WhatsAppTemplateService $templateService
    ): void {
        $queryText = mb_trim($query);
        $perPage = 5;

        $engine = ProductSearchResolver::resolve();
        $paginator = $engine->search([
            'q' => $queryText,
            'is_active' => true,
            'sort_field' => 'created_at',
            'sort_direction' => 'desc',
            'per_page' => $perPage,
        ], $perPage);

        $total = (int) $paginator->total();

        if ($total > 0) {
            $lines = $this->formatProductLines($paginator->items());

            $templateService->sendResultsPrompt(
                $from,
                [
                    'header' => mcp_list_header($total),
                    'lines' => array_slice($lines, 0, $perPage),
                    'footer' => 'Pulsa *Otra búsqueda* para buscar de nuevo o *Pausar* para detener.',
                ]
            );
        } else {
            $templateService->sendNoResultsPrompt($from, [
                'message' => 'No se encontraron productos que coincidan con tu búsqueda',
                'suggestions' => 'Intenta con: *Modelo y marca*, o un *SKU específico*',
            ]);
        }
    }

    /**
     * Formatea los items de productos para la visualización.
     *
     * @param  iterable<mixed>  $items
     * @return array<string>
     */
    private function formatProductLines(iterable $items): array
    {
        $lines = [];
        foreach ($items as $item) {
            if (! $item instanceof Product) {
                continue;
            }

            $sku = $item->sku;
            $name = $item->name;
            $brand = $item->brand;
            $model = $item->model;
            $price = $item->price;
            $stock = $item->stock;
            $active = $item->is_active;

            $lines[] = mcp_product_line(
                is_scalar($sku) ? (string) $sku : '',
                is_scalar($name) ? (string) $name : '',
                is_string($brand) ? $brand : null,
                is_string($model) ? $model : null,
                is_numeric($price) ? (float) $price : 0.0,
                is_numeric($stock) ? (int) $stock : 0,
                is_bool($active) ? $active : true
            );
        }

        return $lines;
    }

    /**
     * Genera una respuesta TwiML vacía estándar.
     */
    private function sendXmlResponse(): Response
    {
        return response('<Response/>', 200)->header('Content-Type', 'text/xml');
    }
}
