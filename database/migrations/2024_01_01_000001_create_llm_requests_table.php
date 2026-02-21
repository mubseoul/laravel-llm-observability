<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('llm-observability.database.tables.requests', 'llm_requests'), function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique()->index();

            // Provider & Model
            $table->string('provider', 50)->index(); // openai, anthropic, ollama, other
            $table->string('model', 100)->index();

            // Attribution
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->string('api_key_id', 50)->nullable()->index();

            // Token Usage
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();

            // Character Counts (fallback)
            $table->unsignedInteger('input_chars')->nullable();
            $table->unsignedInteger('output_chars')->nullable();

            // Performance
            $table->unsignedInteger('latency_ms')->nullable();

            // Cost
            $table->decimal('cost_usd', 10, 6)->nullable()->index();

            // Status
            $table->enum('status', ['success', 'error'])->default('success')->index();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();

            // Optional Bodies (privacy sensitive!)
            $table->longText('prompt_body')->nullable();
            $table->longText('response_body')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // env, route, ip, user_agent, etc.

            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->nullable();

            // Indexes for common queries
            $table->index(['provider', 'model', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['team_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('llm-observability.database.tables.requests', 'llm_requests'));
    }
};
