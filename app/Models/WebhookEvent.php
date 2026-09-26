<?php

namespace App\Models;

use App\Enums\PlatformEnum;
use App\Enums\WebhookEventStatus;
use App\Enums\WebhookEventType;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WebhookEvent extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'connected_account_id',
        'platform',
        'type',
        'external_id',
        'payload_json',
        'status',
        'received_at',
    ];

    protected $casts = [
        'platform' => PlatformEnum::class,
        'type' => WebhookEventType::class,
        'status' => WebhookEventStatus::class,
        'payload_json' => 'array',
        'received_at' => 'datetime',
    ];

    public function connectedAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class);
    }

    public function replyLog(): HasOne
    {
        return $this->hasOne(ReplyLog::class);
    }
}
