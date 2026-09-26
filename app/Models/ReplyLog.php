<?php

namespace App\Models;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplyLog extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'webhook_event_id',
        'matched_rule_id',
        'source',
        'reply_text',
        'status',
        'action_key',
        'meta_response_id',
        'meta_error_code',
        'meta_response_body',
        'sent_at',
    ];

    protected $casts = [
        'source' => ReplySource::class,
        'status' => ReplyStatus::class,
        'meta_response_body' => 'array',
        'sent_at' => 'datetime',
    ];

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    public function matchedRule(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'matched_rule_id');
    }
}
