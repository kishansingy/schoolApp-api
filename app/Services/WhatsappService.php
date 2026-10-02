<?php

namespace App\Services;

use App\Contracts\WhatsappServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService implements WhatsappServiceInterface
{
    private string $token;
    private string $phoneId;
    private string $version;

    public function __construct()
    {
        $this->token   = config('services.whatsapp.token', '');
        $this->phoneId = config('services.whatsapp.phone_id', '');
        $this->version = config('services.whatsapp.version', 'v19.0');
    }

    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->phoneId);
    }

    public function sendText(string $phone, string $message): array
    {
        if (!$this->isConfigured()) {
            return ['success' => true, 'message_id' => 'dev_' . uniqid(), 'dev_mode' => true];
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post("https://graph.facebook.com/{$this->version}/{$this->phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => ['body' => $message, 'preview_url' => false],
                ]);

            Log::info('WhatsApp API response', ['phone' => $phone, 'status' => $response->status(), 'body' => $response->json()]);

            if ($response->successful()) {
                return ['success' => true, 'message_id' => $response->json('messages.0.id')];
            }

            $errMsg = $response->json('error.message')
                ?? $response->json('error.error_data.details')
                ?? ('API error ' . $response->status());

            return ['success' => false, 'error' => $errMsg, 'raw' => $response->json()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendTemplate(string $phone, string $templateName, string $langCode = 'en_US', array $bodyParams = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => true, 'message_id' => 'dev_' . uniqid()];
        }

        try {
            $template = ['name' => $templateName, 'language' => ['code' => $langCode]];

            if (!empty($bodyParams)) {
                $template['components'] = [[
                    'type'       => 'body',
                    'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string) $v], array_values($bodyParams)),
                ]];
            }

            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post("https://graph.facebook.com/{$this->version}/{$this->phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'template',
                    'template'          => $template,
                ]);

            Log::info('WhatsApp Template API response', ['phone' => $phone, 'template' => $templateName, 'status' => $response->status()]);

            if ($response->successful()) {
                return ['success' => true, 'message_id' => $response->json('messages.0.id')];
            }

            return ['success' => false, 'error' => $response->json('error.message') ?? 'Template send failed', 'raw' => $response->json()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (!$digits) return '';
        if (str_starts_with($digits, '0')) $digits = '91' . substr($digits, 1);
        if (strlen($digits) === 10) $digits = '91' . $digits;
        return $digits;
    }
}
