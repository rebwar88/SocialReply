<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\WebhookEventStatus;
use App\Enums\WebhookEventType;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessCommentEvent;
use App\Jobs\ProcessMessageEvent;
use App\Models\ConnectedAccount;
use App\Models\WebhookEvent;
use App\Services\Meta\MetaEventNormalizer;
use App\Services\Meta\MetaWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function __construct(
        private readonly MetaWebhookVerifier $verifier,
        private readonly MetaEventNormalizer $normalizer,
    ) {
    }

    /**
     * Meta's one-time verification handshake when you register the webhook URL.
     */
    public function verify(Request $request): Response
    {
        $challenge = $this->verifier->verifyHandshake(
            $request->query('hub_mode'),
            $request->query('hub_verify_token'),
            $request->query('hub_challenge'),
        );

        return $challenge !== null
            ? response($challenge, 200)
            : response('Verification failed.', 403);
    }

    /**
     * Actual event delivery. Meta expects a fast 2xx response — we persist
     * the raw payload (with a dedupe-safe unique constraint) and defer all
     * real processing to the queue.
     */
    public function handle(Request $request, string $platform): Response
    {
        $payload = $request->all();

        try {
            $events = $this->normalizer->normalize($platform, $payload);
        } catch (\Throwable $e) {
            // Log and still return 200 — Meta will retry on non-2xx, which
            // just re-delivers the same malformed payload. Better to log
            // and move on than get stuck in a retry loop on our side.
            Log::warning('Failed to normalize Meta webhook payload', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return response()->noContent();
        }

        foreach ($events as $event) {
            $this->persistAndDispatch($platform, $event);
        }

        return response()->noContent();
    }

    private function persistAndDispatch(string $platform, $event): void
    {
        $isComment = $event instanceof \App\DTOs\IncomingCommentData;
        $externalId = $isComment ? $event->commentId : $event->messageId;

        $connectedAccount = ConnectedAccount::query()
            ->withoutGlobalScope('workspace')
            ->where('platform', $platform)
            ->where(function ($q) use ($event) {
                $q->where('page_id', $event->pageOrIgAccountId)
                    ->orWhere('ig_business_id', $event->pageOrIgAccountId);
            })
            ->first();

        // firstOrCreate on the (platform, external_id) unique key is the
        // idempotency guarantee: a redelivered webhook hits this exact
        // row instead of inserting a duplicate.
        $webhookEvent = WebhookEvent::query()
            ->withoutGlobalScope('workspace')
            ->firstOrCreate(
                ['platform' => $platform, 'external_id' => $externalId],
                [
                    'workspace_id' => $connectedAccount?->workspace_id,
                    'connected_account_id' => $connectedAccount?->id,
                    'type' => $isComment ? WebhookEventType::Comment : WebhookEventType::Message,
                    'payload_json' => (array) $event,
                    'status' => WebhookEventStatus::Received,
                    'received_at' => $event->createdAt,
                ]
            );

        if (! $webhookEvent->wasRecentlyCreated) {
            // Already seen this exact event — nothing further to do.
            return;
        }

        if ($isComment) {
            ProcessCommentEvent::dispatch($webhookEvent->id);
        } else {
            ProcessMessageEvent::dispatch($webhookEvent->id);
        }
    }
}
