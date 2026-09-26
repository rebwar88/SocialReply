<?php

namespace App\Providers;

use App\Contracts\AiReplyGenerator;
use App\Contracts\ReplySender;
use App\Services\ReplyEngine\GraphReplySender;
use App\Services\ReplyEngine\NullAiReplyGenerator;
use Illuminate\Support\ServiceProvider;

class ReplyEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReplySender::class, GraphReplySender::class);

        // Phase 1 placeholder — swap this binding for a real
        // Claude/GPT-backed implementation in Phase 3. Keeping the
        // interface bound from day one means ReplyDecisionService never
        // needs to change when that happens.
        $this->app->bind(AiReplyGenerator::class, NullAiReplyGenerator::class);
    }
}
