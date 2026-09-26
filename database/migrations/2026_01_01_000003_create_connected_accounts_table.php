<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // facebook | instagram
            $table->string('page_id')->nullable();
            $table->string('ig_business_id')->nullable();
            $table->text('access_token'); // encrypted at the model layer
            $table->timestamp('token_expires_at')->nullable();
            $table->json('granted_permissions_json')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('cooldown_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_accounts');
    }
};
