# Package Structure

This document outlines the complete structure of the Laravel LLM Observability package.

```
laravel-llm-observability/
├── .github/
│   └── workflows/
│       └── ci.yml                          # GitHub Actions CI/CD workflow
├── config/
│   └── llm-observability.php               # Package configuration file
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_llm_requests_table.php
│       ├── 2024_01_01_000002_create_llm_usage_aggregates_table.php
│       ├── 2024_01_01_000003_create_llm_quotas_table.php
│       ├── 2024_01_01_000004_create_llm_alert_rules_table.php
│       └── 2024_01_01_000005_create_llm_webhook_deliveries_table.php
├── resources/
│   └── views/
│       └── filament/
│           └── pages/
│               └── dashboard.blade.php     # Filament dashboard view
├── src/
│   ├── Commands/
│   │   ├── PruneLogsCommand.php           # Command to prune old logs
│   │   ├── RecalculateAggregatesCommand.php # Command to recalculate usage aggregates
│   │   └── ShowPricingCommand.php         # Command to display pricing table
│   ├── Contracts/
│   │   ├── PricingProvider.php            # Interface for custom pricing providers
│   │   └── TokenEstimator.php             # Interface for custom token estimators
│   ├── Facades/
│   │   └── LLM.php                        # LLM facade for easy access
│   ├── Filament/
│   │   ├── Pages/
│   │   │   └── LLMDashboard.php          # Dashboard page
│   │   └── Resources/
│   │       ├── LLMRequestResource.php     # Filament resource for requests
│   │       └── LLMRequestResource/
│   │           └── Pages/
│   │               ├── ListLLMRequests.php
│   │               └── ViewLLMRequest.php
│   ├── Http/
│   │   └── Middleware/
│   │       └── EnsureLLMQuota.php         # Middleware for quota enforcement
│   ├── Jobs/
│   │   ├── RecordLLMRequestJob.php        # Async job for recording requests
│   │   ├── ResetDailyQuotasJob.php        # Scheduled job for daily quota reset
│   │   ├── ResetMonthlyQuotasJob.php      # Scheduled job for monthly quota reset
│   │   └── SendWebhookJob.php             # Job for webhook delivery with retry
│   ├── Models/
│   │   ├── LLMAlertRule.php               # Alert rule model
│   │   ├── LLMQuota.php                   # Quota model
│   │   ├── LLMRequest.php                 # Request log model
│   │   ├── LLMUsageAggregate.php          # Usage aggregate model
│   │   └── LLMWebhookDelivery.php         # Webhook delivery audit model
│   ├── Notifications/
│   │   └── QuotaExceededNotification.php  # Notification for quota alerts
│   ├── Services/
│   │   ├── AggregateService.php           # Service for managing usage aggregates
│   │   ├── AlertManager.php               # Service for alert management
│   │   ├── CostCalculator.php             # Service for cost calculations
│   │   ├── LLMRecorder.php                # Main recording service
│   │   ├── QuotaEnforcer.php              # Service for quota enforcement
│   │   └── TokenEstimator.php             # Service for token estimation
│   └── LLMObservabilityServiceProvider.php # Main service provider
├── tests/
│   ├── Feature/
│   │   ├── LLMRecorderTest.php            # Feature tests for recorder
│   │   ├── MiddlewareTest.php             # Feature tests for middleware
│   │   └── QuotaEnforcementTest.php       # Feature tests for quota enforcement
│   ├── Unit/
│   │   ├── CostCalculatorTest.php         # Unit tests for cost calculation
│   │   └── TokenEstimatorTest.php         # Unit tests for token estimation
│   ├── Pest.php                           # Pest configuration
│   └── TestCase.php                       # Base test case
├── .gitattributes                          # Git attributes
├── .gitignore                              # Git ignore patterns
├── CHANGELOG.md                            # Version history
├── CONTRIBUTING.md                         # Contribution guidelines
├── EXAMPLES.md                             # Usage examples
├── LICENSE                                 # MIT License
├── PACKAGE_STRUCTURE.md                    # This file
├── README.md                               # Main documentation
├── SECURITY.md                             # Security policy
├── composer.json                           # Composer dependencies
├── phpstan-baseline.neon                   # PHPStan baseline
├── phpstan.neon                            # PHPStan configuration
├── phpunit.xml                             # PHPUnit/Pest configuration
└── pint.json                               # Laravel Pint configuration
```

## Directory Overview

### `.github/workflows/`
Contains GitHub Actions workflows for CI/CD, including automated testing, static analysis, and code style checks.

### `config/`
Package configuration files that can be published to the Laravel application.

### `database/migrations/`
Database migrations for creating the required tables.

### `resources/views/`
Blade templates for Filament dashboard components.

### `src/Commands/`
Artisan console commands for package maintenance and utilities.

### `src/Contracts/`
PHP interfaces for dependency injection and custom implementations.

### `src/Facades/`
Laravel facades for convenient static access to services.

### `src/Filament/`
Filament admin panel resources, pages, and components.

### `src/Http/Middleware/`
HTTP middleware for quota enforcement and request processing.

### `src/Jobs/`
Queue jobs for async processing and scheduled tasks.

### `src/Models/`
Eloquent models representing database entities.

### `src/Notifications/`
Laravel notification classes for alerts.

### `src/Services/`
Core business logic and service classes.

### `tests/`
Pest/PHPUnit test suite with feature and unit tests.

## Key Files

### `composer.json`
- Defines package metadata, dependencies, and autoloading
- Specifies Laravel 10/11 and PHP 8.2+ compatibility
- Includes dev dependencies for testing and analysis

### `LLMObservabilityServiceProvider.php`
- Registers services, commands, and middleware
- Configures scheduled tasks
- Publishes config and migrations

### `LLM.php` (Facade)
- Provides static access to LLMRecorder service
- Main entry point for package usage

### Configuration File
- `llm-observability.php`: Comprehensive settings for recording, pricing, quotas, alerts, and retention

## Database Schema

### `llm_requests`
Stores detailed logs of all LLM requests with tokens, costs, latency, and metadata.

### `llm_usage_aggregates`
Aggregated usage statistics by scope, period, provider, and model for fast queries.

### `llm_quotas`
Quota limits by scope (global, user, team, API key).

### `llm_alert_rules`
Configurable alert rules with thresholds and notification settings.

### `llm_webhook_deliveries`
Audit log of webhook deliveries with retry tracking.

## Installation Flow

1. `composer require vendor/laravel-llm-observability`
2. Package auto-discovered via `composer.json` extra section
3. `php artisan vendor:publish --tag="llm-observability-config"`
4. `php artisan migrate`
5. (Optional) Install Filament for dashboard

## Extension Points

### Custom Pricing Provider
Implement `PricingProvider` contract for custom pricing logic.

### Custom Token Estimator
Implement `TokenEstimator` contract for specialized token counting.

### Custom Middleware
Extend or replace `EnsureLLMQuota` for custom quota logic.

### Custom Notifications
Add notification channels via alert rule configuration.

## Testing Architecture

- **Feature Tests**: Test complete workflows and integrations
- **Unit Tests**: Test individual service methods
- **SQLite In-Memory**: Fast, isolated test database
- **Pest**: Modern, expressive test syntax
- **PHPStan**: Static analysis for type safety
- **Laravel Pint**: Automatic code formatting
