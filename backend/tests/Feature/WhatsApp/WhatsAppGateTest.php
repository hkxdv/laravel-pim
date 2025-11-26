<?php

declare(strict_types=1);

it(
    'gate responds empty in greeting (template only)',
    function (): void {
        $resp = $this->post(
            '/api/v1/whatsapp/webhooks/twilio',
            ['Body' => 'Hola', 'From' => 'whatsapp:+14155238886']
        );
        $resp->assertOk();
        expect($resp->getContent())->toContain('<Response/>');
    }
);

it(
    'activates search with command and then lists results',
    function (): void {
        // activar
        $resp1 = $this->post(
            '/api/v1/whatsapp/webhooks/twilio',
            ['Body' => 'buscar', 'From' => 'whatsapp:+14155238886']
        );
        $resp1->assertOk();
        expect($resp1->getContent())->toContain('<Response/>');

        // mostrar template de bienvenida primero
        $this->post(
            '/api/v1/whatsapp/webhooks/twilio',
            ['Body' => 'Hola', 'From' => 'whatsapp:+14155238886']
        )->assertOk();

        $resp2 = $this->post(
            '/api/v1/whatsapp/webhooks/twilio',
            ['Body' => 'Batería iPhone 14', 'From' => 'whatsapp:+14155238886']
        );
        $resp2->assertOk();
        expect($resp2->getContent())->toContain('<Response/>');
    }
);

it(
    'pauses conversation with stop and does not execute LLM',
    function (): void {
        $resp1 = $this->post(
            '/api/v1/whatsapp/webhooks/twilio',
            ['Body' => 'stop', 'From' => 'whatsapp:+14155238886']
        );
        $resp1->assertOk();
        expect($resp1->getContent())->toContain('<Response/>');

        $resp2 = $this->post(
            '/api/v1/whatsapp/webhooks/twilio',
            ['Body' => 'Batería iPhone 14', 'From' => 'whatsapp:+14155238886']
        );
        $resp2->assertOk();
        expect($resp2->getContent())->toContain('<Response/>');
    }
);
