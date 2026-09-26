<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connected_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger_type'); // keyword | contains | regex | exact
            $table->string('trigger_value');
            $table->string('match_scope')->default('both'); // comment | dm | both
            $table->text('response_text');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'connected_account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
