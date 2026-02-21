<?php

namespace Vendor\LLMObservability\Contracts;

interface PricingProvider
{
    /**
     * Get the pricing for a specific provider and model.
     *
     * @return array{input: float, output: float}|null
     */
    public function getPricing(string $provider, string $model): ?array;

    /**
     * Calculate the cost for a given usage.
     */
    public function calculateCost(
        string $provider,
        string $model,
        int $promptTokens,
        int $completionTokens
    ): float;
}
