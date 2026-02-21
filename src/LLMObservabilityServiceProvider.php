<?php

namespace Vendor\LLMObservability;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vendor\LLMObservability\Commands\PruneLogsCommand;
use Vendor\LLMObservability\Commands\RecalculateAggregatesCommand;
use Vendor\LLMObservability\Commands\ShowPricingCommand;
use Vendor\LLMObservability\Http\Middleware\EnsureLLMQuota;
use Vendor\LLMObservability\Jobs\ResetDailyQuotasJob;
use Vendor\LLMObservability\Jobs\ResetMonthlyQuotasJob;
use Vendor\LLMObservability\Services\LLMRecorder;

class LLMObservabilityServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('llm-observability')
            ->hasConfigFile()
            ->hasMigrations([
                '2024_01_01_000001_create_llm_requests_table',
                '2024_01_01_000002_create_llm_usage_aggregates_table',
                '2024_01_01_000003_create_llm_quotas_table',
                '2024_01_01_000004_create_llm_alert_rules_table',
                '2024_01_01_000005_create_llm_webhook_deliveries_table',
            ])
            ->hasCommands([
                PruneLogsCommand::class,
                RecalculateAggregatesCommand::class,
                ShowPricingCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Register the LLM Recorder as a singleton
        $this->app->singleton('llm-recorder', function ($app) {
            return $app->make(LLMRecorder::class);
        });

        // Register middleware alias
        $this->app['router']->aliasMiddleware('llm.quota', EnsureLLMQuota::class);
    }

    public function packageBooted(): void
    {
        // Register scheduled tasks
        $this->registerScheduledTasks();

        // Register routes if dashboard is enabled
        if (config('llm-observability.dashboard.enabled', true)) {
            $this->registerRoutes();
        }

        // Configure logging channel
        $this->configureLogging();
    }

    protected function registerScheduledTasks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Reset daily quotas at midnight
            $schedule->job(new ResetDailyQuotasJob())->daily()->at('00:01');

            // Reset monthly quotas on the first of each month
            $schedule->job(new ResetMonthlyQuotasJob())->monthlyOn(1, '00:05');

            // Prune old logs based on retention settings
            $retentionDays = config('llm-observability.retention.requests_days');
            if ($retentionDays !== null) {
                $schedule->command('llm:prune --all --force')->daily()->at('02:00');
            }
        });
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('llm-observability.dashboard.prefix', 'llm-observability'),
            'middleware' => config('llm-observability.dashboard.middleware', ['web', 'auth']),
        ], function () {
            // Routes will be defined here if using custom controllers
            // For Filament, routes are auto-registered
        });
    }

    protected function configureLogging(): void
    {
        $config = $this->app['config'];

        $config->set('logging.channels.llm-observability', [
            'driver' => 'single',
            'path' => storage_path('logs/llm-observability.log'),
            'level' => 'debug',
        ]);
    }
}
