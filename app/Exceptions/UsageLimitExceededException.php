<?php

namespace App\Exceptions;

use Exception;

class UsageLimitExceededException extends Exception
{
    public static function forWorkspace(int $workspaceId, string $counter): self
    {
        return new self("Workspace #{$workspaceId} has exceeded its {$counter} quota for this billing period.");
    }
}
