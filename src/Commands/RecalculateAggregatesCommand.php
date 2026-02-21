<?php

namespace Mubseoul\LLMObservability\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Mubseoul\LLMObservability\Services\AggregateService;

class RecalculateAggregatesCommand extends Command
{
    protected $signature = 'llm:recalc-aggregates
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--force : Skip confirmation}';

    protected $description = 'Recalculate LLM usage aggregates from raw request data';

    public function handle(AggregateService $aggregateService): int
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subDays(90);
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();

        $this->info("Recalculating aggregates from {$from->toDateString()} to {$to->toDateString()}");

        if (!$this->option('force') && !$this->confirm('This will delete and recalculate all aggregates in the date range. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Recalculating aggregates...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        $aggregateService->recalculateAggregates($from, $to);

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Aggregates recalculated successfully.');

        return 0;
    }
}
