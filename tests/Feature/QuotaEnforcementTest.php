<?php

use Mubseoul\LLMObservability\Models\LLMQuota;
use Mubseoul\LLMObservability\Services\QuotaEnforcer;

beforeEach(function () {
    config(['llm-observability.quotas.enabled' => true]);
});

it('allows requests when under quota', function () {
    $enforcer = app(QuotaEnforcer::class);

    LLMQuota::create([
        'scope' => 'user',
        'scope_id' => '1',
        'requests_per_day' => 100,
        'enabled' => true,
    ]);

    $result = $enforcer->checkQuota(userId: 1);

    expect($result['allowed'])->toBeTrue();
});

it('blocks requests when quota exceeded', function () {
    $enforcer = app(QuotaEnforcer::class);

    LLMQuota::create([
        'scope' => 'user',
        'scope_id' => '1',
        'requests_per_day' => 0, // Already at limit
        'enabled' => true,
    ]);

    // Create a usage aggregate showing we've used the quota
    \Mubseoul\LLMObservability\Models\LLMUsageAggregate::create([
        'scope' => 'user',
        'scope_id' => '1',
        'period' => 'day',
        'period_start' => now()->startOfDay()->toDateString(),
        'period_end' => now()->endOfDay()->toDateString(),
        'total_requests' => 10,
        'successful_requests' => 10,
        'failed_requests' => 0,
        'total_tokens' => 1000,
        'prompt_tokens' => 500,
        'completion_tokens' => 500,
        'total_cost_usd' => 1.0,
        'total_latency_ms' => 5000,
    ]);

    $result = $enforcer->checkQuota(userId: 1);

    expect($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toContain('Daily request limit exceeded');
});

it('checks token limits', function () {
    $enforcer = app(QuotaEnforcer::class);

    LLMQuota::create([
        'scope' => 'user',
        'scope_id' => '1',
        'tokens_per_day' => 100,
        'enabled' => true,
    ]);

    $result = $enforcer->checkQuota(userId: 1, estimatedTokens: 200);

    expect($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toContain('token limit');
});

it('checks cost limits', function () {
    $enforcer = app(QuotaEnforcer::class);

    LLMQuota::create([
        'scope' => 'user',
        'scope_id' => '1',
        'cost_per_day' => 1.00,
        'enabled' => true,
    ]);

    $result = $enforcer->checkQuota(userId: 1, estimatedCost: 2.00);

    expect($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toContain('cost limit');
});

it('respects disabled quotas', function () {
    $enforcer = app(QuotaEnforcer::class);

    LLMQuota::create([
        'scope' => 'user',
        'scope_id' => '1',
        'requests_per_day' => 0,
        'enabled' => false, // Disabled
    ]);

    $result = $enforcer->checkQuota(userId: 1);

    expect($result['allowed'])->toBeTrue();
});
