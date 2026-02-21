<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('llm-observability.database.tables.aggregates', 'llm_usage_aggregates'), function (Blueprint $table) {
            $table->id();

            // Scope
            $table->enum('scope', ['global', 'user', 'team', 'api_key'])->index();
            $table->string('scope_id')->nullable()->index(); // user_id, team_id, or api_key_id

            // Time Period
            $table->enum('period', ['day', 'month'])->index();
            $table->date('period_start')->index();
            $table->date('period_end')->index();

            // Provider & Model (optional, for detailed breakdowns)
            $table->string('provider', 50)->nullable()->index();
            $table->string('model', 100)->nullable()->index();

            // Aggregated Metrics
            $table->unsignedBigInteger('total_requests')->default(0);
            $table->unsignedBigInteger('successful_requests')->default(0);
            $table->unsignedBigInteger('failed_requests')->default(0);

            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->unsignedBigInteger('prompt_tokens')->default(0);
            $table->unsignedBigInteger('completion_tokens')->default(0);

            $table->decimal('total_cost_usd', 12, 6)->default(0);

            $table->unsignedBigInteger('total_latency_ms')->default(0);
            $table->unsignedInteger('avg_latency_ms')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['scope', 'scope_id', 'period', 'period_start', 'provider', 'model'], 'usage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('llm-observability.database.tables.aggregates', 'llm_usage_aggregates'));
    }
};
