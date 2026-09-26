<?php

namespace App\Services\Meta;

use App\DTOs\IncomingCommentData;
use App\DTOs\IncomingMessageData;
use App\Enums\PlatformEnum;
use App\Exceptions\WebhookPayloadInvalidException;

/**
 * Facebook and Instagram deliver webhook payloads under the same top-level
 * "entry" structure, but the nested "changes"/"messaging" shapes differ
 * between comments and Messenger/IG DMs. This class is the one place that
 * knows about those differences — everything downstream works with DTOs.
 *
 * NOTE: field paths here follow Meta's Graph API v19+ webhook shapes as of
 * this writing. Always verify against a live sandbox payload before
 * relying on this in production — Meta does change these periodically.
 */
class MetaEventNormalizer
{
    /**
     * @return array<int, IncomingCommentData|IncomingMessageData>
     */
    public function normalize(string $platform, array $payload): array
    {
        $events = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageOrIgAccountId = (string) ($entry['id'] ?? '');

            // Comment events arrive under "changes" with field "feed" (FB)
            // or "comments" (IG).
            foreach ($entry['changes'] ?? [] as $change) {
                if (in_array($change['field'] ?? null, ['feed', 'comments'], true)) {
                    $events[] = $this->normalizeComment($platform, $pageOrIgAccountId, $change['value'] ?? []);
                }
            }

            // DM/Messenger events arrive under "messaging".
            foreach ($entry['messaging'] ?? [] as $messaging) {
                if (isset($messaging['message'])) {
                    $events[] = $this->normalizeMessage($platform, $pageOrIgAccountId, $messaging);
                }
            }
        }

        return array_filter($events);
    }

    private function normalizeComment(string $platform, string $pageOrIgAccountId, array $value): ?IncomingCommentData
    {
        // Meta sends non-comment "feed" changes too (e.g. status updates,
        // reactions) — skip anything that isn't actually a comment add.
        if (($value['item'] ?? null) !== 'comment' || ($value['verb'] ?? null) !== 'add') {
            return null;
        }

        if (! isset($value['comment_id'])) {
            throw WebhookPayloadInvalidException::missingField('comment_id');
        }

        return new IncomingCommentData(
            platform: PlatformEnum::from($platform),
            pageOrIgAccountId: $pageOrIgAccountId,
            commentId: (string) $value['comment_id'],
            parentCommentId: isset($value['parent_id']) ? (string) $value['parent_id'] : null,
            postId: (string) ($value['post_id'] ?? ''),
            fromUserId: (string) ($value['from']['id'] ?? ''),
            fromUsername: $value['from']['name'] ?? null,
            text: (string) ($value['message'] ?? ''),
            createdAt: isset($value['created_time'])
                ? new \DateTimeImmutable('@' . $value['created_time'])
                : new \DateTimeImmutable(),
        );
    }

    private function normalizeMessage(string $platform, string $pageOrIgAccountId, array $messaging): IncomingMessageData
    {
        if (! isset($messaging['message']['mid'])) {
            throw WebhookPayloadInvalidException::missingField('message.mid');
        }

        return new IncomingMessageData(
            platform: PlatformEnum::from($platform),
            pageOrIgAccountId: $pageOrIgAccountId,
            messageId: (string) $messaging['message']['mid'],
            senderPsid: (string) ($messaging['sender']['id'] ?? ''),
            text: (string) ($messaging['message']['text'] ?? ''),
            createdAt: isset($messaging['timestamp'])
                ? new \DateTimeImmutable('@' . intdiv((int) $messaging['timestamp'], 1000))
                : new \DateTimeImmutable(),
        );
    }
}
