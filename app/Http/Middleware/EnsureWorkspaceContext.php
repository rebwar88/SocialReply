<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves which workspace the current authenticated user is acting as
 * (e.g. via a route parameter or a header) and binds it into the
 * container so the BelongsToWorkspace global scope can use it.
 *
 * This only covers the HTTP request lifecycle. Queued jobs run outside
 * it and must bind their own workspace context explicitly — see
 * ProcessCommentEvent for the job-side equivalent.
 */
class EnsureWorkspaceContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = $request->route('workspace')
            ?? $request->header('X-Workspace-Id');

        if (! $workspaceId) {
            abort(400, 'Workspace context is required.');
        }

        $workspace = Workspace::query()->findOrFail($workspaceId);

        abort_unless(
            $request->user()?->workspaces()->whereKey($workspace->id)->exists(),
            403,
            'You do not have access to this workspace.'
        );

        app()->instance('current.workspace_id', $workspace->id);

        return $next($request);
    }
}
