<?php

namespace App\DTOs;

use App\Enums\ReplySource;

/**
 * The outcome of ReplyDecisionService: what to say and where it came from,
 * before anything has been sent.
 */
final class ReplyDecisionData
{
    public function __construct(
        public readonly bool $shouldReply,
        public readonly ?string $replyText,
        public readonly ReplySource $source,
        public readonly ?int $matchedRuleId = null,
        public readonly ?string $skipReason = null,
    ) {
    }

    public static function skip(string $reason): self
    {
        return new self(
            shouldReply: false,
            replyText: null,
            source: ReplySource::Manual,
            skipReason: $reason,
        );
    }
}
