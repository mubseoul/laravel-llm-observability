<?php

use Mubseoul\LLMObservability\Services\TokenEstimator;

it('can estimate tokens from character count', function () {
    $estimator = new TokenEstimator();

    config(['llm-observability.token_estimation.chars_per_token' => 4]);

    $tokens = $estimator->estimateTokens(400);

    expect($tokens)->toBe(100);
});

it('can estimate tokens from text', function () {
    $estimator = new TokenEstimator();

    config(['llm-observability.token_estimation.chars_per_token' => 4]);

    $text = 'This is a test message that should be approximately 16 tokens long based on character count.';
    $tokens = $estimator->estimateFromText($text);

    expect($tokens)->toBeGreaterThan(0);
});

it('rounds up partial tokens', function () {
    $estimator = new TokenEstimator();

    config(['llm-observability.token_estimation.chars_per_token' => 4]);

    $tokens = $estimator->estimateTokens(5); // 5 chars = 1.25 tokens, should round to 2

    expect($tokens)->toBe(2);
});
