<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('llm-observability.database.tables.quotas', 'llm_quotas'), function (Blueprint $table) {
            $table->id();

            // Scope
            $table->enum('scope', ['global', 'user', 'team', 'api_key'])->index();
            $table->string('scope_id')->nullable()->index();

            // Quota Limits (null = no limit)
            $table->unsignedInteger('requests_per_day')->nullable();
            $table->unsignedInteger('requests_per_month')->nullable();

            $table->unsignedBigInteger('tokens_per_day')->nullable();
            $table->unsignedBigInteger('tokens_per_month')->nullable();

            $table->decimal('cost_per_day', 10, 2)->nullable();
            $table->decimal('cost_per_month', 10, 2)->nullable();

            // Status
            $table->boolean('enabled')->default(true);

            $table->timestamps();

            // Unique constraint
            $table->unique(['scope', 'scope_id'], 'quota_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('llm-observability.database.tables.quotas', 'llm_quotas'));
    }
};
