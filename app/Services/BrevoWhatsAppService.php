<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoWhatsAppService
{
    /**
     * Invia un messaggio WhatsApp usando un template con variabili NOMINALI ({{CLIENTE}}).
     */
    public function sendTemplate(string $to, int $templateId, array $params = [])
    {
        $url = 'https://api.brevo.com/v3/whatsapp/sendMessage';

        // Pulisco numeri da + o spazi
        $toClean     = preg_replace('/\D+/', '', $to);
        $senderClean = preg_replace('/\D+/', '', env('BREVO_WHATSAPP_NUMBER'));
 
        $payload = [
            "senderNumber"   => $senderClean,
            "contactNumbers" => [$toClean],
            "type"           => "template",
            "templateId"     => $templateId,  
            "params"         => $params,       
        ];

        $response = Http::withHeaders([
            'api-key'      => env('BREVO_API_KEY'),
            'accept'       => 'application/json',
            'content-type' => 'application/json',
        ])->post($url, $payload);

        Log::info('Brevo WhatsApp Response', [
            'status'  => $response->status(),
            'body'    => $response->json(),
            'payload' => $payload,
        ]);

        return $response->json();
    }
}
