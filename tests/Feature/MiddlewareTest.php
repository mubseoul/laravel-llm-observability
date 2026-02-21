<?php

use Illuminate\Http\Request;
use Vendor\LLMObservability\Http\Middleware\EnsureLLMQuota;
use Vendor\LLMObservability\Models\LLMQuota;

it('allows requests when quota is not exceeded', function () {
    $middleware = app(EnsureLLMQuota::class);

    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['success' => true]);
    });

    expect($response->status())->toBe(200);
});

it('blocks requests when quota is exceeded', function () {
    LLMQuota::create([
        'scope' => 'global',
        'scope_id' => null,
        'requests_per_day' => 0,
        'enabled' => true,
    ]);

    // Create usage showing quota is exceeded
    \Vendor\LLMObservability\Models\LLMUsageAggregate::create([
        'scope' => 'global',
        'scope_id' => null,
        'period' => 'day',
        'period_start' => now()->startOfDay()->toDateString(),
        'period_end' => now()->endOfDay()->toDateString(),
        'total_requests' => 100,
        'successful_requests' => 100,
        'failed_requests' => 0,
        'total_tokens' => 10000,
        'prompt_tokens' => 5000,
        'completion_tokens' => 5000,
        'total_cost_usd' => 10.0,
        'total_latency_ms' => 50000,
    ]);

    $middleware = app(EnsureLLMQuota::class);

    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['success' => true]);
    });

    expect($response->status())->toBe(429)
        ->and($response->getData(true))->toHaveKey('error');
});
