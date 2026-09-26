<?php

namespace App\Exceptions;

use Exception;

class MetaTokenExpiredException extends Exception
{
    public static function forAccount(int $connectedAccountId): self
    {
        return new self("Access token for connected account #{$connectedAccountId} is expired or revoked.");
    }
}
