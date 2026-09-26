<?php

namespace App\Services\ReplyEngine;

use App\Contracts\AiReplyGenerator;

/**
 * Phase 1 placeholder. Since ai_fallback_enabled defaults to false in
 * ReplyDecisionService, this should never actually be called yet — it
 * exists so the AiReplyGenerator contract is bound and injectable from
 * day one, and swapping it for a real implementation in Phase 3 requires
 * no changes anywhere else.
 */
class NullAiReplyGenerator implements AiReplyGenerator
{
    public function generate(string $incomingText, array $workspaceGuardrails = []): string
    {
        throw new \RuntimeException('AI fallback is not yet implemented (Phase 3).');
    }
}
