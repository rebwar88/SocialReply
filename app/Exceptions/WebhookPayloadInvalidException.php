<?php

namespace App\Exceptions;

use Exception;

class WebhookPayloadInvalidException extends Exception
{
    public static function missingField(string $field): self
    {
        return new self("Webhook payload is missing expected field: {$field}");
    }
}
