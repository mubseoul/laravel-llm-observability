<?php

namespace Mubseoul\LLMObservability\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mubseoul\LLMObservability\Models\LLMQuota;
use Mubseoul\LLMObservability\Models\LLMUsageAggregate;

class QuotaEnforcer
{
    public function __construct(
        protected CostCalculator $costCalculator,
        protected TokenEstimator $tokenEstimator
    ) {
    }

    /**
     * Check if a request would exceed quotas.
     *
     * @return array{allowed: bool, reason: ?string, quota_type: ?string}
     */
    public function checkQuota(
        ?int $userId = null,
        ?int $teamId = null,
        ?string $apiKeyId = null,
        int $estimatedTokens = 0,
        float $estimatedCost = 0.0
    ): array {
        if (!config('llm-observability.quotas.enabled', true)) {
            return ['allowed' => true, 'reason' => null, 'quota_type' => null];
        }

        // Check in order: user, team, global
        $checks = [
            ['scope' => 'user', 'id' => $userId],
            ['scope' => 'team', 'id' => $teamId],
            ['scope' => 'api_key', 'id' => $apiKeyId],
            ['scope' => 'global', 'id' => null],
        ];

        foreach ($checks as $check) {
            if ($check['id'] === null && $check['scope'] !== 'global') {
                continue;
            }

            $result = $this->checkScopeQuota(
                $check['scope'],
                $check['id'],
                $estimatedTokens,
                $estimatedCost
            );

            if (!$result['allowed']) {
                return $result;
            }
        }

        return ['allowed' => true, 'reason' => null, 'quota_type' => null];
    }

    protected function checkScopeQuota(
        string $scope,
        ?string $scopeId,
        int $estimatedTokens,
        float $estimatedCost
    ): array {
        $quota = $this->getQuota($scope, $scopeId);

        if ($quota === null || !$quota->enabled) {
            return ['allowed' => true, 'reason' => null, 'quota_type' => null];
        }

        // Get current usage
        $dailyUsage = $this->getCurrentUsage($scope, $scopeId, 'day');
        $monthlyUsage = $this->getCurrentUsage($scope, $scopeId, 'month');

        // Check daily limits
        if ($quota->requests_per_day !== null) {
            if ($dailyUsage['requests'] >= $quota->requests_per_day) {
                return [
                    'allowed' => false,
                    'reason' => 'Daily request limit exceeded',
                    'quota_type' => 'requests_per_day',
                ];
            }
        }

        if ($quota->tokens_per_day !== null) {
            if ($dailyUsage['tokens'] + $estimatedTokens > $quota->tokens_per_day) {
                return [
                    'allowed' => false,
                    'reason' => 'Daily token limit exceeded',
                    'quota_type' => 'tokens_per_day',
                ];
            }
        }

        if ($quota->cost_per_day !== null) {
            if ($dailyUsage['cost'] + $estimatedCost > (float) $quota->cost_per_day) {
                return [
                    'allowed' => false,
                    'reason' => 'Daily cost limit exceeded',
                    'quota_type' => 'cost_per_day',
                ];
            }
        }

        // Check monthly limits
        if ($quota->requests_per_month !== null) {
            if ($monthlyUsage['requests'] >= $quota->requests_per_month) {
                return [
                    'allowed' => false,
                    'reason' => 'Monthly request limit exceeded',
                    'quota_type' => 'requests_per_month',
                ];
            }
        }

        if ($quota->tokens_per_month !== null) {
            if ($monthlyUsage['tokens'] + $estimatedTokens > $quota->tokens_per_month) {
                return [
                    'allowed' => false,
                    'reason' => 'Monthly token limit exceeded',
                    'quota_type' => 'tokens_per_month',
                ];
            }
        }

        if ($quota->cost_per_month !== null) {
            if ($monthlyUsage['cost'] + $estimatedCost > (float) $quota->cost_per_month) {
                return [
                    'allowed' => false,
                    'reason' => 'Monthly cost limit exceeded',
                    'quota_type' => 'cost_per_month',
                ];
            }
        }

        return ['allowed' => true, 'reason' => null, 'quota_type' => null];
    }

    protected function getQuota(string $scope, ?string $scopeId): ?LLMQuota
    {
        $query = LLMQuota::where('scope', $scope);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        } else {
            $query->whereNull('scope_id');
        }

        return $query->first();
    }

    protected function getCurrentUsage(string $scope, ?string $scopeId, string $period): array
    {
        $now = Carbon::now();
        $start = $period === 'day' ? $now->startOfDay() : $now->startOfMonth();
        $end = $period === 'day' ? $now->endOfDay() : $now->endOfMonth();

        $query = LLMUsageAggregate::forScope($scope, $scopeId)
            ->forPeriod($period)
            ->where('period_start', '>=', $start->toDateString())
            ->where('period_end', '<=', $end->toDateString());

        $aggregate = $query->first();

        return [
            'requests' => $aggregate?->total_requests ?? 0,
            'tokens' => $aggregate?->total_tokens ?? 0,
            'cost' => (float) ($aggregate?->total_cost_usd ?? 0),
        ];
    }

    public function getOrCreateQuota(string $scope, ?string $scopeId = null): LLMQuota
    {
        $query = LLMQuota::where('scope', $scope);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        } else {
            $query->whereNull('scope_id');
        }

        $quota = $query->first();

        if ($quota === null) {
            $defaults = config("llm-observability.quotas.defaults.{$scope}", []);

            $quota = LLMQuota::create([
                'scope' => $scope,
                'scope_id' => $scopeId,
                'requests_per_day' => $defaults['requests_per_day'] ?? null,
                'requests_per_month' => $defaults['requests_per_month'] ?? null,
                'tokens_per_day' => $defaults['tokens_per_day'] ?? null,
                'tokens_per_month' => $defaults['tokens_per_month'] ?? null,
                'cost_per_day' => $defaults['cost_per_day'] ?? null,
                'cost_per_month' => $defaults['cost_per_month'] ?? null,
                'enabled' => true,
            ]);
        }

        return $quota;
    }
}
