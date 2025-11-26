<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Http\Controllers;

use App\Http\Controllers\Controller;
// use App\Models\AgentLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\App\Services\Search\ProductSearchResolver;
use Modules\WhatsApp\App\Services\WhatsAppConversationService;
use Modules\WhatsApp\App\Services\WhatsAppTemplateService;

final class TwilioWebhookController extends Controller
{
    /**
     * Maneja los mensajes entrantes de WhatsApp desde Twilio.
     */
    public function handle(
        Request $request,
        WhatsAppConversationService $sessions,
        WhatsAppTemplateService $templates
    ): Response {
        hrtime(true);
        DB::enableQueryLog();
        $message = $request->input('Body');
        $from = $request->input('From');

        Log::info('Twilio webhook received', [
            'from' => $from,
            'body' => $message,
        ]);

        $fromNorm = is_string($from) ? $from : 'unknown';
        $session = $sessions->get($fromNorm);

        $msgStr = is_string($message) ? $message : '';
        $isBtnSearch = preg_match('/^\s*buscar\s*$/iu', $msgStr) === 1;
        $isBtnPause = preg_match('/^\s*pausar\s*$/iu', $msgStr) === 1;
        $isBtnResume = preg_match('/^\s*reanudar\s*$/iu', $msgStr) === 1;

        if ($session->muted_until && now()->lt($session->muted_until)) {
            if ($isBtnResume) {
                $sessions->unmute($session);
                $greetRaw = config('services.whatsapp.gate_greeting', '');
                $greet = is_string($greetRaw) && $greetRaw !== '' ? $greetRaw : '';

                if ($greet === '') {
                    $greet = 'Hola, soy el Bot asistente de inventario,';
                }

                $vars = ['greeting' => $greet];
                $res = $templates->sendGatePrompt($fromNorm, $vars);
                Log::info('Twilio gate welcome result', $res);
            } else {
                $meta = is_array($session->meta ?? null) ? $session->meta : [];
                $sentOnce = (bool) ($meta['resume_sent_once'] ?? false);
                if (! $sentOnce) {
                    $vars = ['greeting' => ''];
                    $res = $templates->sendResumePrompt($fromNorm, $vars);
                    Log::info('Twilio resume template result', $res);
                    $meta['resume_sent_once'] = true;
                    $meta['resume_sent_at'] = Date::now()->toISOString();
                    $session->meta = $meta;
                    $session->save();
                } else {
                    Log::info('Twilio resume template skipped', [
                        'reason' => 'sent_once',
                    ]);
                }
            }

            return response('<Response/>', 200)->header('Content-Type', 'text/xml');
        }

        if ($isBtnPause) {
            $expireMinRaw = config('services.whatsapp.gate_expire_minutes', 1440);
            $expireMin = is_numeric($expireMinRaw)
                ? (int) $expireMinRaw : 1440;
            $sessions->mute($session, $expireMin);
            $vars = ['greeting' => ''];
            $templates->sendResumePrompt($fromNorm, $vars);

            return response('<Response/>', 200)->header('Content-Type', 'text/xml');
        }

        if ($isBtnSearch) {
            $sessions->enableSearch($session);
            $prompt = 'Escribe el nombre, modelo o SKU del producto';
            $sample = 'Batería iPhone 14';
            $res = $templates->sendQueryPrompt($fromNorm, [
                'prompt' => $prompt,
                'sample' => $sample,
            ]);
            Log::info('Twilio query prompt result', $res);

            return response('<Response/>', 200)->header('Content-Type', 'text/xml');
        }

        Log::info('Twilio gate state', [
            'search_enabled' => (bool) $session->search_enabled,
            'muted_until' => (string) (
                $session->muted_until?->toISOString() ?? ''
            ),
        ]);

        $meta = is_array($session->meta ?? null) ? $session->meta : [];
        $welcomeShown = (bool) ($meta['welcome_shown'] ?? false);
        if (! $welcomeShown) {
            $greetRaw = config('services.whatsapp.gate_greeting', '');
            $greet = is_string($greetRaw) && $greetRaw !== '' ? $greetRaw : '';
            if ($greet === '') {
                $greet = 'Hola, soy el Bot asistente de inventario,';
            }

            $vars = ['greeting' => $greet];
            $res = $templates->sendGatePrompt($fromNorm, $vars);
            Log::info('Twilio gate welcome result', $res);
            $sessions->markWelcomeShown($session);

            return response('<Response/>', 200)->header('Content-Type', 'text/xml');
        }

        if (! $session->search_enabled) {
            return response('<Response/>', 200)->header('Content-Type', 'text/xml');
        }

        $queryText = mb_trim($msgStr);
        if ($queryText !== '') {
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
                $lines = [];
                foreach ($paginator->items() as $item) {
                    /** @var \Modules\Inventory\App\Models\Product $item */
                    $skuRaw = $item->getAttribute('sku');
                    $nameRaw = $item->getAttribute('name');
                    $priceRaw = $item->getAttribute('price');
                    $stockRaw = $item->getAttribute('stock');
                    $activeRaw = $item->getAttribute('is_active');

                    $lines[] = mcp_product_line(
                        is_scalar($skuRaw) ? (string) $skuRaw : '',
                        is_scalar($nameRaw) ? (string) $nameRaw : '',
                        is_string($item->getAttribute('brand')) ? $item->getAttribute('brand') : null,
                        is_string($item->getAttribute('model')) ? $item->getAttribute('model') : null,
                        is_numeric($priceRaw) ? (float) $priceRaw : 0.0,
                        is_numeric($stockRaw) ? (int) $stockRaw : 0,
                        is_bool($activeRaw) ? $activeRaw : true
                    );
                }

                $header = mcp_list_header($total);
                $footer = mcp_list_footer($perPage);
                $res = $templates->sendResultsPrompt($fromNorm, [
                    'header' => $header,
                    'lines' => array_slice($lines, 0, $perPage),
                    'footer' => $footer,
                ]);
                Log::info('Twilio results prompt result', $res);

                return response('<Response/>', 200)->header('Content-Type', 'text/xml');
            }

            $res = $templates->sendNoResultsPrompt($fromNorm, [
                'message' => 'No se encontraron productos que coincidan con tu búsqueda',
                'suggestions' => 'Intenta con: Modelo y marca, o un SKU específico',
            ]);
            Log::info('Twilio no-results prompt result', $res);

            return response('<Response/>', 200)->header('Content-Type', 'text/xml');
        }

        return response('<Response/>', 200)->header('Content-Type', 'text/xml');
    }
}
