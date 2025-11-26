<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'openrouter' => [
        'provider' => env('LLM_PROVIDER', 'openrouter'),
        'model' => env('LLM_MODEL', 'meta-llama/llama-3.2-3b-instruct'),
        'api_key' => env('OPENROUTER_API_KEY', ''),
        'referrer' => env('OPENROUTER_REFERRER', ''),
        'title' => env('OPENROUTER_TITLE', ''),
        'max_tokens' => env('OPENROUTER_MAX_TOKENS', 256),
    ],

    'twilio' => [
        'from' => env('TWILIO_WHATSAPP_FROM', ''),
        'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
        'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
    ],

    'whatsapp' => [
        'ai_enabled' => env('WHATSAPP_AI_ENABLED', 'true'),
        'gate_enabled' => env('WHATSAPP_GATE_ENABLED', 'true'),
        'gate_greeting' => env('WHATSAPP_GATE_GREETING', ''),
        'gate_always' => env('WHATSAPP_GATE_ALWAYS', 'true'),
        'gate_expire_minutes' => env('WHATSAPP_GATE_EXPIRE_MINUTES', 1440),
        'gate_template_sid' => env('WHATSAPP_GATE_TEMPLATE_SID', ''),
        'resume_template_sid' => env('WHATSAPP_RESUME_TEMPLATE_SID', ''),
        'query_template_sid' => env('WHATSAPP_QUERY_TEMPLATE_SID', ''),
        'results_template_sid' => env('WHATSAPP_RESULTS_TEMPLATE_SID', ''),
        'no_results_template_sid' => env('WHATSAPP_NO_RESULTS_TEMPLATE_SID', ''),
    ],
];
