<?php

namespace App\Services\ReplyEngine;

use App\Contracts\ReplySender;
use App\Enums\ReplyStatus;
use App\Enums\WebhookEventType;
use App\Models\ReplyLog;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Support\Facades\Log;

class GraphReplySender implements ReplySender
{
    public function __construct(private readonly MetaGraphClient $graphClient)
    {
    }

    public function send(ReplyLog $replyLog): void
    {
        $webhookEvent = $replyLog->webhookEvent()->withoutGlobalScope('workspace')->first();
        $account = $webhookEvent->connectedAccount()->withoutGlobalScope('workspace')->first();

        try {
            $result = $webhookEvent->type === WebhookEventType::Comment
                ? $this->graphClient->replyToComment(
                    $account,
                    $webhookEvent->payload_json['commentId'],
                    $replyLog->reply_text
                )
                : $this->graphClient->sendMessage(
                    $account,
                    $webhookEvent->payload_json['senderPsid'],
                    $replyLog->reply_text
                );
        } catch (\Throwable $e) {
            // Network-level failure — we genuinely don't know if Meta
            // received it before the connection dropped. Mark unknown
            // rather than failed so it surfaces for manual review instead
            // of silently retrying (which could double-send).
            Log::error('SendReply network exception', ['reply_log_id' => $replyLog->id, 'error' => $e->getMessage()]);
            $replyLog->update(['status' => ReplyStatus::Unknown]);
            return;
        }

        if ($result['success']) {
            $replyLog->update([
                'status' => ReplyStatus::Sent,
                'meta_response_id' => $result['response_id'],
                'meta_response_body' => $result['body'],
                'sent_at' => now(),
            ]);
            return;
        }

        $replyLog->update([
            'status' => ReplyStatus::Failed,
            'meta_error_code' => $result['error_code'],
            'meta_response_body' => $result['body'],
        ]);

        if ($this->graphClient->isTransientErrorCode($result['error_code'])) {
            // Let the job's own retry/backoff mechanism handle this by
            // throwing — Laravel will re-run handle() per $tries/$backoff.
            throw new \RuntimeException("Transient Graph API error: {$result['error_code']}");
        }

        // Permanent error: status is already 'failed', nothing more to do.
        // Consider notifying the workspace here (Phase 6 notifications).
    }
}
