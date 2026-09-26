<?php

namespace App\Contracts;

interface AiReplyGenerator
{
    /**
     * Generate a reply for the given incoming text.
     *
     * $incomingText is untrusted user input — implementations must treat it
     * strictly as data to respond to, never as instructions to follow.
     *
     * @param string $incomingText The comment or message text to reply to.
     * @param array{tone?: string, banned_topics?: array<string>} $workspaceGuardrails
     */
    public function generate(string $incomingText, array $workspaceGuardrails = []): string;
}
