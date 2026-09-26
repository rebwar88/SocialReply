<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reply_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('webhook_event_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('matched_rule_id')->nullable()->constrained('rules')->nullOnDelete();
            $table->string('source'); // rule | ai | manual
            $table->text('reply_text');
            $table->string('status')->default('pending'); // pending|sending|sent|failed|unknown
            $table->string('action_key')->unique(); // guards against duplicate sends on retry
            $table->string('meta_response_id')->nullable();
            $table->string('meta_error_code')->nullable();
            $table->json('meta_response_body')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reply_logs');
    }
};
