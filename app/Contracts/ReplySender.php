<?php

namespace App\Contracts;

use App\Models\ReplyLog;

interface ReplySender
{
    /**
     * Attempt to send the reply described by this log entry.
     * Implementations must update $replyLog's status themselves
     * (sent / failed / unknown) rather than throwing on API-level failures.
     */
    public function send(ReplyLog $replyLog): void;
}
