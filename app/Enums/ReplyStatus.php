<?php

namespace App\Enums;

enum ReplyStatus: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Unknown = 'unknown';
}
