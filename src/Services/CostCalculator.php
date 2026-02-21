<?php

namespace Mubseoul\LLMObservability\Services;

use Mubseoul\LLMObservability\Contracts\PricingProvider as PricingProviderContract;

class CostCalculator implements PricingProviderContract
{
    public function getPricing(string $provider, string $model): ?array
    {
        $pricing = config('llm-observability.pricing', []);

        // Try exact match
        if (isset($pricing[$provider][$model])) {
            return $pricing[$provider][$model];
        }

        // Try partial match (for versioned models)
        if (isset($pricing[$provider])) {
            foreach ($pricing[$provider] as $configModel => $prices) {
                if (str_contains($model, $configModel) || str_contains($configModel, $model)) {
                    return $prices;
                }
            }
        }

        // Fallback to default for provider
        if (isset($pricing[$provider]['default'])) {
            return $pricing[$provider]['default'];
        }

        // Fallback to global default
        return $pricing['other']['default'] ?? null;
    }

    public function calculateCost(
        string $provider,
        string $model,
        int $promptTokens,
        int $completionTokens
    ): float {
        $pricing = $this->getPricing($provider, $model);

        if ($pricing === null) {
            return 0.0;
        }

        // Pricing is per 1M tokens
        $inputCost = ($promptTokens / 1_000_000) * ($pricing['input'] ?? 0);
        $outputCost = ($completionTokens / 1_000_000) * ($pricing['output'] ?? 0);

        return round($inputCost + $outputCost, 6);
    }

    public function calculateCostFromTotal(string $provider, string $model, int $totalTokens): float
    {
        $pricing = $this->getPricing($provider, $model);

        if ($pricing === null) {
            return 0.0;
        }

        // Use average of input/output pricing for total tokens
        $averagePrice = (($pricing['input'] ?? 0) + ($pricing['output'] ?? 0)) / 2;
        $cost = ($totalTokens / 1_000_000) * $averagePrice;

        return round($cost, 6);
    }
}
