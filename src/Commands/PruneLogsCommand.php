<?php

namespace Vendor\LLMObservability\Commands;

use Illuminate\Console\Command;
use Vendor\LLMObservability\Models\LLMRequest;
use Vendor\LLMObservability\Models\LLMUsageAggregate;
use Vendor\LLMObservability\Models\LLMWebhookDelivery;

class PruneLogsCommand extends Command
{
    protected $signature = 'llm:prune
                            {--requests : Prune old request logs}
                            {--aggregates : Prune old usage aggregates}
                            {--webhooks : Prune old webhook deliveries}
                            {--all : Prune all log types}
                            {--force : Skip confirmation}';

    protected $description = 'Prune old LLM observability logs based on retention settings';

    public function handle(): int
    {
        $pruneAll = $this->option('all');
        $force = $this->option('force');

        if (!$force && !$this->confirm('Are you sure you want to prune old logs?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        if ($this->option('requests') || $pruneAll) {
            $this->pruneRequests();
        }

        if ($this->option('aggregates') || $pruneAll) {
            $this->pruneAggregates();
        }

        if ($this->option('webhooks') || $pruneAll) {
            $this->pruneWebhooks();
        }

        if (!$this->option('requests') && !$this->option('aggregates') && !$this->option('webhooks') && !$pruneAll) {
            $this->warn('Please specify what to prune: --requests, --aggregates, --webhooks, or --all');
            return 1;
        }

        $this->info('Log pruning completed successfully.');
        return 0;
    }

    protected function pruneRequests(): void
    {
        $days = config('llm-observability.retention.requests_days');

        if ($days === null) {
            $this->info('Request log retention is disabled. Skipping.');
            return;
        }

        $cutoffDate = now()->subDays($days);
        $count = LLMRequest::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No request logs to prune.');
            return;
        }

        LLMRequest::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Pruned {$count} request logs older than {$days} days.");
    }

    protected function pruneAggregates(): void
    {
        $days = config('llm-observability.retention.aggregates_days');

        if ($days === null) {
            $this->info('Aggregate retention is disabled. Skipping.');
            return;
        }

        $cutoffDate = now()->subDays($days);
        $count = LLMUsageAggregate::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No usage aggregates to prune.');
            return;
        }

        LLMUsageAggregate::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Pruned {$count} usage aggregates older than {$days} days.");
    }

    protected function pruneWebhooks(): void
    {
        $days = config('llm-observability.retention.webhook_deliveries_days');

        if ($days === null) {
            $this->info('Webhook delivery retention is disabled. Skipping.');
            return;
        }

        $cutoffDate = now()->subDays($days);
        $count = LLMWebhookDelivery::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('No webhook deliveries to prune.');
            return;
        }

        LLMWebhookDelivery::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Pruned {$count} webhook deliveries older than {$days} days.");
    }
}
