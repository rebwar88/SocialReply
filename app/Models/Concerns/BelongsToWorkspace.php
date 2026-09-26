<?php

namespace App\Models\Concerns;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applied to every tenant-owned model. Auto-scopes queries to the
 * "current workspace" set on the container (see EnsureWorkspaceContext
 * middleware for the HTTP path, and jobs must set this explicitly since
 * they run outside the request lifecycle — see ProcessCommentEvent).
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            if (app()->bound('current.workspace_id')) {
                $builder->where(
                    $builder->getModel()->getTable() . '.workspace_id',
                    app('current.workspace_id')
                );
            }
        });

        static::creating(function ($model) {
            if (! $model->workspace_id && app()->bound('current.workspace_id')) {
                $model->workspace_id = app('current.workspace_id');
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
