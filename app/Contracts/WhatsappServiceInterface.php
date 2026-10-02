<?php

namespace App\Contracts;

interface WhatsappServiceInterface
{
    public function sendText(string $phone, string $message): array;
    public function sendTemplate(string $phone, string $templateName, string $langCode = 'en_US', array $bodyParams = []): array;
    public function normalizePhone(string $phone): string;
    public function isConfigured(): bool;
}
