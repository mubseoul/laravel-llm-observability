<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('llm-observability.database.tables.alert_rules', 'llm_alert_rules'), function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            // Alert Type
            $table->enum('type', [
                'cost_threshold',
                'error_rate',
                'latency_spike',
                'quota_exceeded',
                'token_limit',
            ])->index();

            // Scope
            $table->enum('scope', ['global', 'user', 'team', 'api_key'])->default('global');
            $table->string('scope_id')->nullable();

            // Threshold Configuration (JSON)
            $table->json('threshold_config'); // e.g., {"cost_usd": 100, "period": "day"}

            // Notification Settings
            $table->boolean('send_notification')->default(true);
            $table->json('notification_channels')->nullable(); // ['mail', 'slack']

            // Webhook Settings
            $table->boolean('send_webhook')->default(false);
            $table->string('webhook_url')->nullable();

            // Status
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_triggered_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('llm-observability.database.tables.alert_rules', 'llm_alert_rules'));
    }
};
