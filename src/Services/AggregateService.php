<?php

namespace Vendor\LLMObservability\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Vendor\LLMObservability\Models\LLMUsageAggregate;

class AggregateService
{
    public function updateAggregatesForRequest(array $payload): void
    {
        $scopes = $this->getScopesForRequest($payload);

        foreach ($scopes as $scope) {
            $this->updateAggregate($scope, $payload, 'day');
            $this->updateAggregate($scope, $payload, 'month');
        }
    }

    protected function getScopesForRequest(array $payload): array
    {
        $scopes = [
            ['scope' => 'global', 'scope_id' => null],
        ];

        if (!empty($payload['user_id'])) {
            $scopes[] = ['scope' => 'user', 'scope_id' => (string) $payload['user_id']];
        }

        if (!empty($payload['team_id'])) {
            $scopes[] = ['scope' => 'team', 'scope_id' => (string) $payload['team_id']];
        }

        if (!empty($payload['api_key_id'])) {
            $scopes[] = ['scope' => 'api_key', 'scope_id' => $payload['api_key_id']];
        }

        return $scopes;
    }

    protected function updateAggregate(array $scope, array $payload, string $period): void
    {
        $now = Carbon::parse($payload['created_at'] ?? now());
        $periodStart = $period === 'day' ? $now->copy()->startOfDay() : $now->copy()->startOfMonth();
        $periodEnd = $period === 'day' ? $now->copy()->endOfDay() : $now->copy()->endOfMonth();

        $aggregate = LLMUsageAggregate::firstOrCreate(
            [
                'scope' => $scope['scope'],
                'scope_id' => $scope['scope_id'],
                'period' => $period,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'provider' => $payload['provider'] ?? null,
                'model' => $payload['model'] ?? null,
            ],
            [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_tokens' => 0,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_cost_usd' => 0,
                'total_latency_ms' => 0,
                'avg_latency_ms' => 0,
            ]
        );

        $isSuccess = ($payload['status'] ?? 'success') === 'success';

        DB::table($aggregate->getTable())
            ->where('id', $aggregate->id)
            ->update([
                'total_requests' => DB::raw('total_requests + 1'),
                'successful_requests' => DB::raw($isSuccess ? 'successful_requests + 1' : 'successful_requests'),
                'failed_requests' => DB::raw(!$isSuccess ? 'failed_requests + 1' : 'failed_requests'),
                'total_tokens' => DB::raw('total_tokens + ' . ($payload['total_tokens'] ?? 0)),
                'prompt_tokens' => DB::raw('prompt_tokens + ' . ($payload['prompt_tokens'] ?? 0)),
                'completion_tokens' => DB::raw('completion_tokens + ' . ($payload['completion_tokens'] ?? 0)),
                'total_cost_usd' => DB::raw('total_cost_usd + ' . ($payload['cost_usd'] ?? 0)),
                'total_latency_ms' => DB::raw('total_latency_ms + ' . ($payload['latency_ms'] ?? 0)),
                'updated_at' => now(),
            ]);

        // Update average latency
        $aggregate->refresh();
        $aggregate->update([
            'avg_latency_ms' => $aggregate->total_requests > 0
                ? (int) ($aggregate->total_latency_ms / $aggregate->total_requests)
                : 0,
        ]);
    }

    public function recalculateAggregates(?Carbon $startDate = null, ?Carbon $endDate = null): void
    {
        $startDate = $startDate ?? Carbon::now()->subDays(90);
        $endDate = $endDate ?? Carbon::now();

        // Clear existing aggregates for the date range
        LLMUsageAggregate::whereBetween('period_start', [$startDate, $endDate])->delete();

        // Recalculate from raw requests
        $table = config('llm-observability.database.tables.requests', 'llm_requests');

        $requests = DB::table($table)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        foreach ($requests as $request) {
            $this->updateAggregatesForRequest((array) $request);
        }
    }
}
