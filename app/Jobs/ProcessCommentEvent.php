<?php

namespace App\Jobs;

use App\Enums\ReplyStatus;
use App\Enums\WebhookEventStatus;
use App\Models\ReplyLog;
use App\Models\WebhookEvent;
use App\Services\ReplyEngine\ReplyDecisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessCommentEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $webhookEventId)
    {
    }

    public function handle(ReplyDecisionService $decisionService): void
    {
        // Jobs run outside the HTTP request lifecycle, so workspace context
        // must be established here rather than inherited — see
        // BelongsToWorkspace's use of app('current.workspace_id').
        $webhookEvent = WebhookEvent::query()->withoutGlobalScope('workspace')->findOrFail($this->webhookEventId);
        app()->instance('current.workspace_id', $webhookEvent->workspace_id);

        // Idempotency: if a retry lands here after a ReplyLog was already
        // written for this event, don't decide/enqueue a second time.
        if (ReplyLog::query()->where('webhook_event_id', $webhookEvent->id)->exists()) {
            return;
        }

        $account = $webhookEvent->connectedAccount;

        if (! $account) {
            $webhookEvent->update(['status' => WebhookEventStatus::Failed]);
            return;
        }

        $payload = $webhookEvent->payload_json;
        $decision = $decisionService->decide($account, $payload['text'] ?? '', 'comment');

        if (! $decision->shouldReply) {
            $webhookEvent->update(['status' => WebhookEventStatus::Processed]);
            return;
        }

        $replyLog = ReplyLog::create([
            'workspace_id' => $webhookEvent->workspace_id,
            'webhook_event_id' => $webhookEvent->id,
            'matched_rule_id' => $decision->matchedRuleId,
            'source' => $decision->source,
            'reply_text' => $decision->replyText,
            'status' => ReplyStatus::Pending,
            'action_key' => (string) Str::uuid(),
        ]);

        $webhookEvent->update(['status' => WebhookEventStatus::Processed]);

        SendReply::dispatch($replyLog->id);
    }
}
