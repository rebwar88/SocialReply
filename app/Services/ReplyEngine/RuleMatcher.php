<?php

namespace App\Services\ReplyEngine;

use App\Models\ConnectedAccount;
use App\Models\Rule;
use Illuminate\Support\Facades\Cache;

class RuleMatcher
{
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Finds the highest-priority active rule whose trigger matches $text,
     * scoped to rules that apply to this account (account-specific or
     * account-agnostic) and to this match scope (comment/dm/both).
     */
    public function match(ConnectedAccount $account, string $text, string $matchScope): ?Rule
    {
        $rules = $this->activeRulesFor($account);

        foreach ($rules as $rule) {
            if (! in_array($rule->match_scope, [$matchScope, 'both'], true)) {
                continue;
            }

            if ($this->triggerMatches($rule, $text)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Rule>
     */
    private function activeRulesFor(ConnectedAccount $account): \Illuminate\Support\Collection
    {
        $cacheKey = "rules:active:account:{$account->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($account) {
            return Rule::query()
                ->where('workspace_id', $account->workspace_id)
                ->where(function ($q) use ($account) {
                    $q->whereNull('connected_account_id')
                        ->orWhere('connected_account_id', $account->id);
                })
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->get();
        });
    }

    /**
     * Call this from Rule creating/updating/deleting observers so the
     * cache never serves a stale rule set.
     */
    public static function invalidateCacheFor(int $connectedAccountId): void
    {
        Cache::forget("rules:active:account:{$connectedAccountId}");
    }

    private function triggerMatches(Rule $rule, string $text): bool
    {
        $haystack = mb_strtolower($text);
        $needle = mb_strtolower($rule->trigger_value);

        return match ($rule->trigger_type) {
            'exact' => $haystack === $needle,
            'contains', 'keyword' => str_contains($haystack, $needle),
            'regex' => (bool) @preg_match($rule->trigger_value, $text),
            default => false,
        };
    }
}
