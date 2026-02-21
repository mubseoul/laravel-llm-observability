<?php

namespace Mubseoul\LLMObservability\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mubseoul\LLMObservability\Models\LLMAlertRule;
use Mubseoul\LLMObservability\Models\LLMWebhookDelivery;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30];

    public function __construct(
        protected LLMAlertRule $alertRule,
        protected ?array $customPayload = null
    ) {
    }

    public function handle(): void
    {
        $payload = $this->customPayload ?? $this->buildPayload();

        $delivery = LLMWebhookDelivery::create([
            'alert_rule_id' => $this->alertRule->id,
            'webhook_url' => $this->alertRule->webhook_url,
            'payload' => $payload,
            'status' => 'pending',
            'attempt' => $this->attempts(),
        ]);

        try {
            $timeout = config('llm-observability.alerts.webhooks.timeout', 10);

            $response = Http::timeout($timeout)
                ->post($this->alertRule->webhook_url, $payload);

            if ($response->successful()) {
                $delivery->markSuccess($response->status(), $response->body());
                Log::channel('llm-observability')->info('Webhook delivered successfully', [
                    'alert_rule_id' => $this->alertRule->id,
                    'delivery_id' => $delivery->id,
                ]);
            } else {
                $delivery->markFailed('HTTP ' . $response->status(), $response->status());
                $this->fail(new \Exception('Webhook failed with status ' . $response->status()));
            }
        } catch (\Throwable $e) {
            $delivery->markFailed($e->getMessage());
            Log::channel('llm-observability')->error('Webhook delivery failed', [
                'alert_rule_id' => $this->alertRule->id,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function buildPayload(): array
    {
        return [
            'event' => 'alert_triggered',
            'alert_rule' => [
                'id' => $this->alertRule->id,
                'name' => $this->alertRule->name,
                'type' => $this->alertRule->type,
                'description' => $this->alertRule->description,
            ],
            'threshold_config' => $this->alertRule->threshold_config,
            'triggered_at' => now()->toIso8601String(),
            'scope' => $this->alertRule->scope,
            'scope_id' => $this->alertRule->scope_id,
        ];
    }

    public function tags(): array
    {
        return [
            'llm-observability',
            'webhook',
            'alert:' . $this->alertRule->type,
        ];
    }
}
