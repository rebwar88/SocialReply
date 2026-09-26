<?php

namespace App\Enums;

enum ReplySource: string
{
    case Rule = 'rule';
    case Ai = 'ai';
    case Manual = 'manual';
}
