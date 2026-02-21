<?php

namespace Vendor\LLMObservability\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Vendor\LLMObservability\Models\LLMUsageAggregate;

class ResetMonthlyQuotasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lastMonth = now()->subMonth()->startOfMonth()->toDateString();

        // Archive last month's monthly aggregates
        $aggregates = LLMUsageAggregate::forPeriod('month')
            ->where('period_start', $lastMonth)
            ->get();

        Log::channel('llm-observability')->info('Monthly quotas reset', [
            'month' => $lastMonth,
            'aggregates_count' => $aggregates->count(),
        ]);

        // Note: We don't actually delete the aggregates, they serve as historical data
        // The system will automatically create new aggregates for this month
    }

    public function tags(): array
    {
        return ['llm-observability', 'quota-reset', 'monthly'];
    }
}
