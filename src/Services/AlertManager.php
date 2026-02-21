<?php

namespace Vendor\LLMObservability\Services;

use Illuminate\Support\Facades\Notification;
use Vendor\LLMObservability\Jobs\SendWebhookJob;
use Vendor\LLMObservability\Models\LLMAlertRule;
use Vendor\LLMObservability\Models\LLMUsageAggregate;
use Vendor\LLMObservability\Notifications\QuotaExceededNotification;

class AlertManager
{
    public function checkAlerts(): void
    {
        if (!config('llm-observability.alerts.enabled', true)) {
            return;
        }

        $rules = LLMAlertRule::enabled()->get();

        foreach ($rules as $rule) {
            $this->evaluateRule($rule);
        }
    }

    protected function evaluateRule(LLMAlertRule $rule): void
    {
        $shouldTrigger = match ($rule->type) {
            'cost_threshold' => $this->evaluateCostThreshold($rule),
            'error_rate' => $this->evaluateErrorRate($rule),
            'latency_spike' => $this->evaluateLatencySpike($rule),
            'quota_exceeded' => $this->evaluateQuotaExceeded($rule),
            'token_limit' => $this->evaluateTokenLimit($rule),
            default => false,
        };

        if ($shouldTrigger) {
            $this->triggerAlert($rule);
        }
    }

    protected function evaluateCostThreshold(LLMAlertRule $rule): bool
    {
        $costUsd = $rule->getThreshold('cost_usd');
        $period = $rule->getThreshold('period', 'day');

        $aggregate = $this->getAggregate($rule->scope, $rule->scope_id, $period);

        return $aggregate && $aggregate->total_cost_usd >= $costUsd;
    }

    protected function evaluateErrorRate(LLMAlertRule $rule): bool
    {
        $errorRatePercent = $rule->getThreshold('error_rate_percent');
        $period = $rule->getThreshold('period', 'day');

        $aggregate = $this->getAggregate($rule->scope, $rule->scope_id, $period);

        return $aggregate && $aggregate->getErrorRate() >= $errorRatePercent;
    }

    protected function evaluateLatencySpike(LLMAlertRule $rule): bool
    {
        $latencyMs = $rule->getThreshold('latency_ms');
        $period = $rule->getThreshold('period', 'day');

        $aggregate = $this->getAggregate($rule->scope, $rule->scope_id, $period);

        return $aggregate && $aggregate->avg_latency_ms >= $latencyMs;
    }

    protected function evaluateQuotaExceeded(LLMAlertRule $rule): bool
    {
        $quotaType = $rule->getThreshold('quota_type');
        $period = $rule->getThreshold('period', 'day');

        $aggregate = $this->getAggregate($rule->scope, $rule->scope_id, $period);

        if (!$aggregate) {
            return false;
        }

        // This is a simplified check; in reality, you'd compare against quota limits
        return false;
    }

    protected function evaluateTokenLimit(LLMAlertRule $rule): bool
    {
        $tokenLimit = $rule->getThreshold('token_limit');
        $period = $rule->getThreshold('period', 'day');

        $aggregate = $this->getAggregate($rule->scope, $rule->scope_id, $period);

        return $aggregate && $aggregate->total_tokens >= $tokenLimit;
    }

    protected function getAggregate(string $scope, ?string $scopeId, string $period): ?LLMUsageAggregate
    {
        $now = now();
        $start = $period === 'day' ? $now->copy()->startOfDay() : $now->copy()->startOfMonth();
        $end = $period === 'day' ? $now->copy()->endOfDay() : $now->copy()->endOfMonth();

        return LLMUsageAggregate::forScope($scope, $scopeId)
            ->forPeriod($period)
            ->where('period_start', $start->toDateString())
            ->where('period_end', $end->toDateString())
            ->first();
    }

    protected function triggerAlert(LLMAlertRule $rule): void
    {
        // Mark as triggered
        $rule->markTriggered();

        // Send notification
        if ($rule->shouldNotify()) {
            $this->sendNotification($rule);
        }

        // Send webhook
        if ($rule->shouldSendWebhook()) {
            $this->sendWebhook($rule);
        }
    }

    protected function sendNotification(LLMAlertRule $rule): void
    {
        $channels = $rule->notification_channels ?? config('llm-observability.alerts.notification_channels', ['mail']);

        // Get the notifiable (e.g., admin user)
        $user = config('llm-observability.alerts.admin_user');

        if ($user) {
            Notification::route('mail', $user)
                ->notify(new QuotaExceededNotification($rule));
        }
    }

    protected function sendWebhook(LLMAlertRule $rule): void
    {
        SendWebhookJob::dispatch($rule);
    }
}
