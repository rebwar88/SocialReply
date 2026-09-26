<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('connected_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform');
            $table->string('type'); // comment | message
            $table->string('external_id'); // Meta's comment/message id
            $table->json('payload_json');
            $table->string('status')->default('received');
            $table->timestamp('received_at');
            $table->timestamps();

            // The core idempotency guarantee: Meta can and will redeliver
            // webhooks. This constraint makes double-insertion impossible
            // at the DB level, independent of any application-level check.
            $table->unique(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
