<?php

namespace App\Services\ReplyEngine;

use App\Contracts\AiReplyGenerator;
use App\DTOs\ReplyDecisionData;
use App\Enums\ReplySource;
use App\Models\ConnectedAccount;

class ReplyDecisionService
{
    public function __construct(
        private readonly RuleMatcher $ruleMatcher,
        private readonly AiReplyGenerator $aiReplyGenerator,
    ) {
    }

    public function decide(ConnectedAccount $account, string $incomingText, string $matchScope): ReplyDecisionData
    {
        if ($account->isInCooldown()) {
            return ReplyDecisionData::skip('account_in_cooldown');
        }

        if ($account->status->value !== 'active') {
            return ReplyDecisionData::skip('account_not_active');
        }

        if (trim($incomingText) === '') {
            return ReplyDecisionData::skip('empty_incoming_text');
        }

        // TODO (Phase 5): usage reservation check goes here —
        // reserve a unit from UsageCounter before proceeding, release it
        // if nothing is ultimately sent.

        $rule = $this->ruleMatcher->match($account, $incomingText, $matchScope);

        if ($rule !== null) {
            return new ReplyDecisionData(
                shouldReply: true,
                replyText: $rule->response_text,
                source: ReplySource::Rule,
                matchedRuleId: $rule->id,
            );
        }

        // AI fallback is feature-flagged per workspace/plan in later phases.
        // For Phase 1, this can be a no-op implementation of the interface
        // that simply returns null/throws, effectively disabling fallback
        // until Phase 3 wiring is in place.
        if (! config('services.meta.ai_fallback_enabled', false)) {
            return ReplyDecisionData::skip('no_rule_matched_ai_disabled');
        }

        $aiText = $this->aiReplyGenerator->generate($incomingText);

        return new ReplyDecisionData(
            shouldReply: true,
            replyText: $aiText,
            source: ReplySource::Ai,
        );
    }
}
