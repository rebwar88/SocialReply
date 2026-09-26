<?php

namespace App\Services\Meta;

class MetaWebhookVerifier
{
    /**
     * Handles Meta's GET verification handshake: they hit the webhook URL
     * with hub.mode, hub.verify_token, and hub.challenge, and expect the
     * challenge echoed back if the verify token matches.
     */
    public function verifyHandshake(?string $mode, ?string $verifyToken, ?string $challenge): ?string
    {
        $expectedToken = config('services.meta.webhook_verify_token');

        if ($mode === 'subscribe' && $verifyToken === $expectedToken) {
            return $challenge;
        }

        return null;
    }
}
