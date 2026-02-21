<?php

namespace Mubseoul\LLMObservability\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Mubseoul\LLMObservability\LLMObservabilityServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Vendor\\LLMObservability\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LLMObservabilityServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Run migrations
        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000001_create_llm_requests_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000002_create_llm_usage_aggregates_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000003_create_llm_quotas_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000004_create_llm_alert_rules_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000005_create_llm_webhook_deliveries_table.php';
        $migration->up();
    }
}
