<?php

namespace App\DTOs;

use App\Enums\PlatformEnum;

/**
 * Normalized shape of an incoming DM/Messenger event.
 */
final class IncomingMessageData
{
    public function __construct(
        public readonly PlatformEnum $platform,
        public readonly string $pageOrIgAccountId,
        public readonly string $messageId,
        public readonly string $senderPsid,
        public readonly string $text,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
