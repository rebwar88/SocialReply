# Phase 1 Scaffold — Social Auto-Reply SaaS

This is Phase 1 from the architecture doc: Meta OAuth groundwork, webhook
intake with dedupe, the rule engine, and everything wired end-to-end on a
single connected account. AI fallback and full multi-tenant UI are Phase 3+.

## How to install this into a fresh Laravel app

```bash
composer create-project laravel/laravel social-auto-reply
cd social-auto-reply
```

Then copy this scaffold's `app/`, `database/migrations/`, and `routes/webhooks.php`
into the new project (merging into the existing `app/` folder — nothing here
overwrites Laravel's defaults except what's listed below).

### 1. Register the webhook routes
In `bootstrap/app.php` (Laravel 11+) or `RouteServiceProvider` (Laravel 10),
add:
```php
Route::middleware('api')->group(base_path('routes/webhooks.php'));
```

### 2. Register the middleware alias
In `bootstrap/app.php`'s `withMiddleware()` (or `app/Http/Kernel.php` on
Laravel 10), add:
```php
'meta.signature' => \App\Http\Middleware\VerifyMetaSignature::class,
'workspace' => \App\Http\Middleware\EnsureWorkspaceContext::class,
```

### 3. Register the service provider
Add `App\Providers\ReplyEngineServiceProvider::class` to `bootstrap/providers.php`
(Laravel 11+) or `config/app.php`'s providers array (Laravel 10).

### 4. Merge config
Add the `meta` array from `config-additions-services.php` into your
`config/services.php`, and the keys from `env-additions.txt` into `.env`.

### 5. Extend the User model
Add to `app/Models/User.php`:
```php
public function workspaces(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(Workspace::class)->withPivot('role')->withTimestamps();
}
```

### 6. Run migrations
```bash
php artisan migrate
```

### 7. Register Rule cache invalidation
Add a `RuleObserver` (or use model events directly in `Rule::booted()`) that
calls `RuleMatcher::invalidateCacheFor($rule->connected_account_id)` on
`created`, `updated`, and `deleted`. Not included here since it's a small
addition best wired once you see how you're managing rules in the UI.

## What's deliberately NOT in this scaffold yet

- OAuth connect flow (Facebook Login for Business + token exchange) — this
  is mostly HTTP calls to `https://www.facebook.com/v21.0/dialog/oauth` and
  `/oauth/access_token`; worth building once you have real App Review
  access, since the exact scopes/flow depend on what Meta approves.
- Real AI fallback implementation (`NullAiReplyGenerator` is a placeholder).
- `RefreshPageToken` / `SyncAccountInsights` jobs.
- Billing/usage reservation (`ReplyDecisionService` has a TODO marking
  exactly where it plugs in).
- Dashboard/API controllers for managing rules and viewing the inbox.

## Testing this end-to-end without real Meta traffic

1. Manually insert a `ConnectedAccount` row with `page_id` matching a test value.
2. POST a synthetic payload shaped like Meta's comment webhook to
   `/webhooks/meta/facebook` (see `MetaEventNormalizer` for the expected shape).
3. Watch the queue: `WebhookEvent` → `ProcessCommentEvent` → `ReplyLog` (pending)
   → `SendReply` (will fail against the real Graph API without a valid token —
   that's expected until OAuth is wired up; you can stub `MetaGraphClient` in
   a test to verify the full pipeline without hitting Meta at all).
# SocialReply
