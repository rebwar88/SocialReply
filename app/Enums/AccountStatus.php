<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case TokenExpired = 'token_expired';
    case Disconnected = 'disconnected';
    case RateLimited = 'rate_limited';
}
