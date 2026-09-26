<?php

namespace App\Services\Meta;

use App\Models\ConnectedAccount;
use Illuminate\Support\Facades\Http;

class MetaGraphClient
{
    private const BASE_URL = 'https://graph.facebook.com/v21.0';

    /**
     * Reply to a comment. Works for both Facebook and Instagram comment
     * ids via the same endpoint shape.
     *
     * @return array{success: bool, response_id: ?string, error_code: ?string, body: array}
     */
    public function replyToComment(ConnectedAccount $account, string $commentId, string $message): array
    {
        $response = Http::asForm()->post(self::BASE_URL . "/{$commentId}/comments", [
            'message' => $message,
            'access_token' => $account->access_token,
        ]);

        return $this->interpret($response);
    }

    /**
     * Send a Messenger/IG direct message reply.
     */
    public function sendMessage(ConnectedAccount $account, string $recipientPsid, string $message): array
    {
        $response = Http::asJson()->post(self::BASE_URL . '/me/messages', [
            'recipient' => ['id' => $recipientPsid],
            'message' => ['text' => $message],
            'access_token' => $account->access_token,
        ]);

        return $this->interpret($response);
    }

    private function interpret(\Illuminate\Http\Client\Response $response): array
    {
        $body = $response->json() ?? [];

        if ($response->successful()) {
            return [
                'success' => true,
                'response_id' => $body['id'] ?? null,
                'error_code' => null,
                'body' => $body,
            ];
        }

        return [
            'success' => false,
            'response_id' => null,
            'error_code' => (string) ($body['error']['code'] ?? $response->status()),
            'body' => $body,
        ];
    }

    /**
     * Graph API error codes that indicate a transient condition worth
     * retrying (rate limiting, temporary API issues), as opposed to a
     * permanent one (invalid token, permission revoked, content rejected).
     */
    public function isTransientErrorCode(?string $code): bool
    {
        return in_array($code, ['4', '17', '32', '613'], true); // rate limit family
    }
}
