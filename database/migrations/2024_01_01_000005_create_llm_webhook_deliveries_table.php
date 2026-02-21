<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('llm-observability.database.tables.webhook_deliveries', 'llm_webhook_deliveries'), function (Blueprint $table) {
            $table->id();

            $table->foreignId('alert_rule_id')->nullable()->constrained(
                config('llm-observability.database.tables.alert_rules', 'llm_alert_rules')
            )->nullOnDelete();

            $table->string('webhook_url');
            $table->json('payload');

            // Delivery Status
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->index();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('llm-observability.database.tables.webhook_deliveries', 'llm_webhook_deliveries'));
    }
};
