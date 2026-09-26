<?php

namespace App\DTOs;

use App\Enums\PlatformEnum;

/**
 * Normalized shape of an incoming comment event, regardless of whether
 * it originated from Facebook or Instagram's webhook payload.
 */
final class IncomingCommentData
{
    public function __construct(
        public readonly PlatformEnum $platform,
        public readonly string $pageOrIgAccountId,
        public readonly string $commentId,
        public readonly ?string $parentCommentId,
        public readonly string $postId,
        public readonly string $fromUserId,
        public readonly ?string $fromUsername,
        public readonly string $text,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
