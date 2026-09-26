<?php

namespace App\Enums;

enum WebhookEventType: string
{
    case Comment = 'comment';
    case Message = 'message';
}
