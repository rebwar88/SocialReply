<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\PlatformEnum;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConnectedAccount extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'platform',
        'page_id',
        'ig_business_id',
        'access_token',
        'token_expires_at',
        'granted_permissions_json',
        'status',
        'cooldown_until',
    ];

    protected $casts = [
        'platform' => PlatformEnum::class,
        'status' => AccountStatus::class,
        'token_expires_at' => 'datetime',
        'cooldown_until' => 'datetime',
        'granted_permissions_json' => 'array',
        // Encrypt the token at rest. Requires an appropriate cast or
        // accessor/mutator pair using Crypt::encryptString/decryptString
        // if you need queryable behavior beyond Laravel's `encrypted` cast.
        'access_token' => 'encrypted',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function isInCooldown(): bool
    {
        return $this->cooldown_until !== null && $this->cooldown_until->isFuture();
    }
}
