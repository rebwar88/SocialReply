<?php

namespace App\Jobs;

use App\Contracts\ReplySender;
use App\Enums\ReplyStatus;
use App\Models\ReplyLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    // Backoff for transient errors (rate limit, timeout). A permanent
    // error (invalid token, permission revoked) should mark the ReplyLog
    // failed inside the sender itself and let the job succeed without
    // retrying — see ReplySender contract docblock.
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly int $replyLogId)
    {
    }

    public function handle(ReplySender $sender): void
    {
        $replyLog = ReplyLog::query()->withoutGlobalScope('workspace')->findOrFail($this->replyLogId);
        app()->instance('current.workspace_id', $replyLog->workspace_id);

        // Already resolved (e.g. a previous attempt succeeded but the job
        // was retried anyway) — never send twice.
        if (in_array($replyLog->status, [ReplyStatus::Sent, ReplyStatus::Sending], true)) {
            return;
        }

        $replyLog->update(['status' => ReplyStatus::Sending]);

        $sender->send($replyLog);
    }
}
