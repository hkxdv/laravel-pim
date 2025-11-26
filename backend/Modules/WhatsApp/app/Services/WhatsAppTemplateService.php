<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Rest\Client;

final class WhatsAppTemplateService
{
    /**
     * @param  array<mixed>  $variables
     * @return array{sid: string}|array{error: string}
     */
    public function sendTemplate(
        string $to,
        string $contentSid,
        array $variables = []
    ): array {
        $fromRaw = Config::get(
            'TWILIO_WHATSAPP_FROM',
            Config::get('services.twilio.from', '')
        );

        $from = is_string($fromRaw) ? $fromRaw : '';
        if ($from !== '' && ! str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:'.$from;
        }

        $accountSidRaw = Config::get(
            'TWILIO_ACCOUNT_SID',
            Config::get('services.twilio.account_sid', '')
        );

        $accountSid = is_string($accountSidRaw) ? $accountSidRaw : '';
        $authTokenRaw = Config::get(
            'TWILIO_AUTH_TOKEN',
            Config::get('services.twilio.auth_token', '')
        );

        $authToken = is_string($authTokenRaw) ? $authTokenRaw : '';

        if (
            $contentSid === '' || $from === '' || $accountSid === '' || $authToken === ''
        ) {
            Log::warning('Twilio template config incomplete', [
                'contentSid_present' => $contentSid !== '',
                'from_present' => $from !== '',
                'account_present' => $accountSid !== '',
                'token_present' => $authToken !== '',
            ]);

            return ['error' => 'twilio_template_not_configured'];
        }

        try {
            $jsonVars = '{}';
            if ($variables !== []) {
                $jsonVars = json_encode($variables, JSON_THROW_ON_ERROR);
            }

            Log::info('Twilio template send attempt', [
                'to' => $to,
                'from' => $from,
                'contentSid' => $contentSid,
                'variables' => $variables,
                'variables_json' => $jsonVars,
            ]);

            $client = new Client($accountSid, $authToken);

            $payload = [
                'from' => $from,
                'contentSid' => $contentSid,
            ];

            if ($jsonVars !== '{}') {
                $payload['contentVariables'] = $jsonVars;
            }

            $resp = $client->messages->create(
                $to,
                $payload
            );

            Log::info('Twilio template send success', [
                'sid' => (string) $resp->sid,
            ]);

            return ['sid' => (string) $resp->sid];
        } catch (Throwable $throwable) {
            Log::warning('Twilio template send failed', [
                'error' => $throwable->getMessage(),
            ]);

            return ['error' => 'twilio_template_send_failed'];
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{sid: string}|array{error: string}
     */
    public function sendGatePrompt(string $to, array $variables = []): array
    {
        $sidRaw = Config::get('services.whatsapp.gate_template_sid', '');
        $sid = is_string($sidRaw) ? $sidRaw : '';

        $fromRaw = Config::get(
            'TWILIO_WHATSAPP_FROM',
            Config::get('services.twilio.from', '')
        );
        $from = is_string($fromRaw) ? $fromRaw : '';
        if ($from !== '' && ! str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:'.$from;
        }

        $accountSidRaw = Config::get(
            'TWILIO_ACCOUNT_SID',
            Config::get('services.twilio.account_sid', '')
        );
        $accountSid = is_string($accountSidRaw) ? $accountSidRaw : '';

        $authTokenRaw = Config::get(
            'TWILIO_AUTH_TOKEN',
            Config::get('services.twilio.auth_token', '')
        );
        $authToken = is_string($authTokenRaw) ? $authTokenRaw : '';

        if (
            $sid === '' || $from === '' || $accountSid === '' || $authToken === ''
        ) {
            Log::warning('Twilio template config incomplete', [
                'contentSid_present' => $sid !== '',
                'from_present' => $from !== '',
                'account_present' => $accountSid !== '',
                'token_present' => $authToken !== '',
            ]);

            return ['error' => 'twilio_template_not_configured'];
        }

        $mapped = [];
        if (isset($variables['greeting']) && is_string($variables['greeting'])) {
            $mapped['1'] = $variables['greeting'];
        }

        Log::info('Twilio gate prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        $res = $this->sendTemplate($to, $sid, $mapped);
        if (
            isset($res['error'])
            && $res['error'] === 'twilio_template_send_failed'
        ) {
            Log::info('Twilio gate prompt retry without variables');
            $res = $this->sendTemplate($to, $sid, []);
        }

        return $res;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{sid: string}|array{error: string}
     */
    public function sendResumePrompt(string $to, array $variables = []): array
    {
        $sidRaw = Config::get('services.whatsapp.resume_template_sid', '');
        $sid = is_string($sidRaw) ? $sidRaw : '';
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
     * @param  array<string, mixed>  $variables
     * @return array{sid: string}|array{error: string}
     */
    public function sendResultsPrompt(string $to, array $variables = []): array
    {
        $sidRaw = Config::get('services.whatsapp.results_template_sid', '');
        $sid = is_string($sidRaw) ? $sidRaw : '';
        $mapped = [];
        if (
            isset($variables['header'])
            && is_string($variables['header'])
        ) {
            $mapped['1'] = $variables['header'];
        }

        $lines = is_array($variables['lines'] ?? null)
            ? $variables['lines'] : [];
        $idx = 2;
        foreach ($lines as $line) {
            if (! is_string($line)) {
                continue;
            }

            if ($idx > 6) {
                break;
            }

            $mapped[(string) $idx] = $line;
            $idx++;
        }

        if (isset($variables['footer']) && is_string($variables['footer'])) {
            $mapped['7'] = $variables['footer'];
        }

        Log::info('Twilio results prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        return $this->sendTemplate($to, $sid, $mapped);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{sid: string}|array{error: string}
     */
    public function sendNoResultsPrompt(
        string $to,
        array $variables = []
    ): array {
        $sidRaw = Config::get('services.whatsapp.no_results_template_sid', '');
        $sid = is_string($sidRaw) ? $sidRaw : '';
        $mapped = [];
        if (
            isset($variables['message'])
            && is_string($variables['message'])
        ) {
            $mapped['1'] = $variables['message'];
        }

        if (
            isset($variables['suggestions'])
            && is_string($variables['suggestions'])
        ) {
            $mapped['2'] = $variables['suggestions'];
        }

        Log::info('Twilio no-results prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        return $this->sendTemplate($to, $sid, $mapped);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{sid: string}|array{error: string}
     */
    public function sendQueryPrompt(string $to, array $variables = []): array
    {
        $sidRaw = Config::get('services.whatsapp.query_template_sid', '');
        $sid = is_string($sidRaw) ? $sidRaw : '';
        $mapped = [];
        if (
            isset($variables['prompt'])
            && is_string($variables['prompt'])
        ) {
            $mapped['1'] = $variables['prompt'];
        }

        if (
            isset($variables['sample'])
            && is_string($variables['sample'])
        ) {
            $mapped['2'] = $variables['sample'];
        }

        Log::info('Twilio query prompt variables', [
            'contentSid' => $sid,
            'variables' => $mapped,
        ]);

        return $this->sendTemplate($to, $sid, $mapped);
    }
}
