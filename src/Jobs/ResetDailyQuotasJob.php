<?php

namespace Vendor\LLMObservability\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Vendor\LLMObservability\Models\LLMUsageAggregate;

class ResetDailyQuotasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();

        // Archive yesterday's daily aggregates
        $aggregates = LLMUsageAggregate::forPeriod('day')
            ->where('period_start', $yesterday)
            ->get();

        Log::channel('llm-observability')->info('Daily quotas reset', [
            'date' => $yesterday,
            'aggregates_count' => $aggregates->count(),
        ]);

        // Note: We don't actually delete the aggregates, they serve as historical data
        // The system will automatically create new aggregates for today
    }

    public function tags(): array
    {
        return ['llm-observability', 'quota-reset', 'daily'];
    }
}
