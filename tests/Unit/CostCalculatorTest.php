<?php

use Vendor\LLMObservability\Services\CostCalculator;

it('can calculate cost for OpenAI GPT-4', function () {
    $calculator = new CostCalculator();

    $cost = $calculator->calculateCost('openai', 'gpt-4', 1000, 500);

    // (1000/1M * $30) + (500/1M * $60) = $0.03 + $0.03 = $0.06
    expect($cost)->toBe(0.06);
});

it('can calculate cost for Anthropic Claude', function () {
    $calculator = new CostCalculator();

    $cost = $calculator->calculateCost('anthropic', 'claude-3-opus-20240229', 1000, 500);

    // (1000/1M * $15) + (500/1M * $75) = $0.015 + $0.0375 = $0.0525
    expect($cost)->toBe(0.0525);
});

it('returns zero cost for unknown provider', function () {
    $calculator = new CostCalculator();

    // Mock config to not have fallback
    config(['llm-observability.pricing.unknown' => null]);

    $cost = $calculator->calculateCost('unknown', 'unknown-model', 1000, 500);

    expect($cost)->toBe(0.0);
});

it('can get pricing for a model', function () {
    $calculator = new CostCalculator();

    $pricing = $calculator->getPricing('openai', 'gpt-4');

    expect($pricing)->toBeArray()
        ->and($pricing)->toHaveKey('input')
        ->and($pricing)->toHaveKey('output');
});

it('uses partial matching for model names', function () {
    $calculator = new CostCalculator();

    $pricing = $calculator->getPricing('openai', 'gpt-4-0125-preview');

    expect($pricing)->toBeArray()
        ->and($pricing['input'])->toBeGreaterThan(0);
});
