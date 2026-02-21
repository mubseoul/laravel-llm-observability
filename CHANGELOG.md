# Changelog

All notable changes to `laravel-llm-observability` will be documented in this file.

## [Unreleased]

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Complete LLM request tracking and observability
- Support for OpenAI, Anthropic, Ollama, and custom providers
- Automatic cost calculation with configurable pricing tables
- Quota enforcement (requests, tokens, cost per day/month)
- Scoped quotas (global, user, team, API key)
- Filament v3 admin dashboard
- Request logs with filtering and search
- Usage analytics and aggregation
- Alert rules with webhook delivery
- Laravel Notifications integration
- Async recording via queue jobs
- Sampling support for high-volume applications
- Data retention and automatic pruning
- Scheduled quota resets
- Middleware for quota enforcement
- Artisan commands:
  - `llm:prune` - Prune old logs
  - `llm:recalc-aggregates` - Recalculate usage aggregates
  - `llm:pricing` - Display pricing table
- Comprehensive test suite with Pest
- Privacy controls and PII redaction
- Custom pricing provider support
- Custom token estimator support
- Multi-provider fallback support
- Batch processing examples
- Extensive documentation and examples

### Security
- Disabled body storage by default
- Automatic sensitive data redaction
- Configurable redaction patterns
- Secure webhook delivery with retry logic

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on how to contribute to this project.
