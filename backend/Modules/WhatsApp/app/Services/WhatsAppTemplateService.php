<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Rest\Client;

/**
 * Servicio encargado de gestionar el envío de plantillas y mensajes de WhatsApp vía Twilio.
 */
final class WhatsAppTemplateService
{
    /**
     * Envía una plantilla de WhatsApp genérica.
     *
     * @param  string  $to  Número de teléfono de destino.
     * @param  string  $contentSid  SID de la plantilla de contenido.
     * @param  array<mixed>  $variables  Variables dinámicas para la plantilla.
     * @return array{sid: string}|array{error: string}
     */
    public function sendTemplate(
        string $to,
        string $contentSid,
        array $variables = []
    ): array {
        $jsonVars = $variables !== []
            ? json_encode($variables, JSON_THROW_ON_ERROR)
            : '{}';

        $payload = ['contentSid' => $contentSid];

        if ($jsonVars !== '{}') {
            $payload['contentVariables'] = $jsonVars;
        }

        return $this->dispatchMessage($to, $payload, [
            'template_sid' => $contentSid,
            'variables' => $variables,
        ]);
    }

    /**
     * Envía un mensaje de texto simple sin plantilla.
     *
     * @param  string  $to  Número de teléfono de destino.
     * @param  string  $message  Cuerpo del mensaje de texto.
     * @return array{sid: string}|array{error: string}
     */
    public function sendTextMessage(string $to, string $message): array
    {
        return $this->dispatchMessage($to, ['body' => $message], [
            'message_body' => $message,
        ]);
    }

    /**
     * Envía la plantilla de bienvenida.
     * Incluye lógica de reintento sin variables si el primer envío falla.
     *
     * @param  string  $to  Número de destino.
     * @param  array<string, mixed>  $variables  Datos para la plantilla (ej: 'greeting').
     * @return array{sid: string}|array{error: string}
     */
    public function sendWelcomePrompt(string $to, array $variables = []): array
    {
        $sid = $this->getConfigSid('welcome_template_sid');
        $mapped = [];

        if (isset($variables['greeting']) && is_string($variables['greeting'])) {
            $mapped['1'] = $variables['greeting'];
        }

        Log::info('Twilio gate prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        $res = $this->sendTemplate($to, $sid, $mapped);

        // Retry logic specífico para welcome prompt
        if (isset($res['error']) && $res['error'] === 'twilio_template_send_failed') {
            Log::info('Twilio gate prompt retry without variables');
            $res = $this->sendTemplate($to, $sid, []);
        }

        return $res;
    }

    /**
     * Envía la plantilla de reanudación de conversación.
     *
     * @param  string  $to  Número de destino.
     * @param  array<string, mixed>  $variables  Datos para la plantilla.
     * @return array{sid: string}|array{error: string}
     */
    public function sendResumePrompt(string $to, array $variables = []): array
    {
        $sid = $this->getConfigSid('resume_template_sid');
        $mapped = [];

        if (isset($variables['greeting']) && is_string($variables['greeting'])) {
            $mapped['1'] = $variables['greeting'];
        }

        Log::info('Twilio resume prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        return $this->sendTemplate($to, $sid, $mapped);
    }

    /**
     * Envía la plantilla de resultados de búsqueda.
     * Mapea hasta 5 líneas de resultados, cabecera y pie.
     *
     * @param  string  $to  Número de destino.
     * @param  array<string, mixed>  $variables  Debe contener 'header', 'lines' (array) y 'footer'.
     * @return array{sid: string}|array{error: string}
     */
    public function sendResultsPrompt(string $to, array $variables = []): array
    {
        $sid = $this->getConfigSid('results_template_sid');

        // Inicializar mapa con claves vacías del 1 al 7
        $mapped = array_fill_keys(array_map(strval(...), range(1, 7)), '');

        if (isset($variables['header']) && is_string($variables['header'])) {
            $mapped['1'] = $variables['header'];
        }

        $lines = is_array($variables['lines'] ?? null)
            ? $variables['lines'] : [];

        // Mapear líneas de productos a las variables 2-6
        for ($i = 0; $i < 5; $i++) {
            $key = (string) ($i + 2);
            $mapped[$key] = isset($lines[$i]) && is_string($lines[$i])
                ? $lines[$i] : '';
        }

        if (isset($variables['footer']) && is_string($variables['footer'])) {
            $mapped['7'] = mb_trim($variables['footer']);
        }

        Log::info('Twilio results prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        return $this->sendTemplate($to, $sid, $mapped);
    }

    /**
     * Envía la plantilla de "sin resultados".
     *
     * @param  string  $to  Número de destino.
     * @param  array<string, mixed>  $variables  'message' y 'suggestions'.
     * @return array{sid: string}|array{error: string}
     */
    public function sendNoResultsPrompt(
        string $to,
        array $variables = []
    ): array {
        $sid = $this->getConfigSid('no_results_template_sid');
        $mapped = [];

        if (isset($variables['message']) && is_string($variables['message'])) {
            $mapped['1'] = $variables['message'];
        }

        if (isset($variables['suggestions']) && is_string($variables['suggestions'])) {
            $mapped['2'] = $variables['suggestions'];
        }

        Log::info('Twilio no-results prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        return $this->sendTemplate($to, $sid, $mapped);
    }

    /**
     * Envía la plantilla de consulta inicial (query prompt).
     * No requiere variables.
     *
     * @param  string  $to  Número de destino.
     * @return array{sid: string}|array{error: string}
     */
    public function sendQueryPrompt(string $to): array
    {
        $sid = $this->getConfigSid('query_template_sid');

        Log::info('Twilio query prompt (static)', [
            'contentSid' => $sid,
        ]);

        return $this->sendTemplate($to, $sid);
    }

    /**
     * Envía una plantilla dinámica con contenido variable en el cuerpo.
     * Útil para ayuda, ejemplos u otros mensajes informativos.
     *
     * @param  string  $to  Número de destino.
     * @param  string  $bodyText  Texto a inyectar en la variable {{1}}.
     * @return array{sid: string}|array{error: string}
     */
    public function sendDynamicPrompt(string $to, string $bodyText): array
    {
        $sid = $this->getConfigSid('dynamic_template_sid');
        $mapped = ['1' => $bodyText];

        Log::info('Twilio dynamic prompt', [
            'contentSid' => $sid,
            'bodyText' => $bodyText,
        ]);

        return $this->sendTemplate($to, $sid, $mapped);
    }

    /**
     * Envía la plantilla de opciones de pausa.
     *
     * @param  string  $to  Número de destino.
     * @return array{sid: string}|array{error: string}
     */
    public function sendPauseOptionsPrompt(string $to): array
    {
        $sid = $this->getConfigSid('pause_options_template_sid');

        Log::info('Twilio pause-options prompt', [
            'contentSid' => $sid,
        ]);

        return $this->sendTemplate($to, $sid);
    }

    /**
     * Recupera y valida la configuración necesaria de Twilio.
     *
     * @return array{account_sid: string, auth_token: string, from: string}|null
     */
    private function getTwilioConfig(): ?array
    {
        $accountSid = Config::get(
            'TWILIO_ACCOUNT_SID',
            Config::get('services.twilio.account_sid', '')
        );
        $authToken = Config::get(
            'TWILIO_AUTH_TOKEN',
            Config::get('services.twilio.auth_token', '')
        );
        $fromRaw = Config::get(
            'TWILIO_WHATSAPP_FROM',
            Config::get('services.twilio.from', '')
        );

        $accountSid = is_string($accountSid) ? $accountSid : '';
        $authToken = is_string($authToken) ? $authToken : '';
        $from = is_string($fromRaw) ? $fromRaw : '';

        if ($from !== '' && ! str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:'.$from;
        }

        if ($accountSid === '' || $authToken === '' || $from === '') {
            Log::warning('Twilio template config incomplete', [
                'account_present' => $accountSid !== '',
                'token_present' => $authToken !== '',
                'from_present' => $from !== '',
            ]);

            return null;
        }

        return [
            'account_sid' => $accountSid,
            'auth_token' => $authToken,
            'from' => $from,
        ];
    }

    /**
     * Helper para obtener SIDs de configuración de forma segura.
     *
     * @param  string  $key  Clave de configuración (ej: 'welcome_template_sid')
     */
    private function getConfigSid(string $key): string
    {
        $val = Config::get('services.whatsapp'.$key, '');

        return is_string($val) ? $val : '';
    }

    /**
     * Método centralizado para enviar mensajes a través de la API de Twilio.
     *
     * @param  string  $to  Destinatario.
     * @param  array<string, mixed>  $payload  Datos a enviar (contentSid o body).
     * @param  array<string, mixed>  $logContext  Contexto adicional para logging.
     * @return array{sid: string}|array{error: string}
     */
    private function dispatchMessage(
        string $to,
        array $payload,
        array $logContext = []
    ): array {
        $config = $this->getTwilioConfig();

        if ($config === null) {
            return ['error' => 'twilio_template_not_configured'];
        }

        // Asegurar que el 'from' esté en el payload
        $payload['from'] = $config['from'];

        try {
            Log::info('Twilio send attempt', array_merge([
                'to' => $to,
                'from' => $config['from'],
            ], $logContext));

            $client = new Client($config['account_sid'], $config['auth_token']);

            $resp = $client->messages->create($to, $payload);

            Log::info('Twilio send success', [
                'sid' => (string) $resp->sid,
            ]);

            return ['sid' => (string) $resp->sid];
        } catch (Throwable $throwable) {
            Log::warning('Twilio send failed', [
                'error' => $throwable->getMessage(),
                'payload_keys' => array_keys($payload),
            ]);

            return ['error' => 'twilio_template_send_failed'];
        }
    }
}
