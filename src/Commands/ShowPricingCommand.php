<?php

namespace Mubseoul\LLMObservability\Commands;

use Illuminate\Console\Command;

class ShowPricingCommand extends Command
{
    protected $signature = 'llm:pricing {provider?}';

    protected $description = 'Display the current LLM pricing table';

    public function handle(): int
    {
        $provider = $this->argument('provider');
        $pricing = config('llm-observability.pricing', []);

        if ($provider) {
            if (!isset($pricing[$provider])) {
                $this->error("Provider '{$provider}' not found in pricing configuration.");
                return 1;
            }

            $this->displayProviderPricing($provider, $pricing[$provider]);
        } else {
            $this->displayAllPricing($pricing);
        }

        $this->newLine();
        $this->comment('Prices are in USD per 1M tokens.');

        return 0;
    }

    protected function displayAllPricing(array $pricing): void
    {
        $this->info('LLM Pricing Configuration');
        $this->newLine();

        foreach ($pricing as $provider => $models) {
            $this->displayProviderPricing($provider, $models);
            $this->newLine();
        }
    }

    protected function displayProviderPricing(string $provider, array $models): void
    {
        $this->line("<fg=cyan;options=bold>{$provider}</>");

        $rows = [];
        foreach ($models as $model => $prices) {
            $rows[] = [
                $model,
                '$' . number_format($prices['input'], 2),
                '$' . number_format($prices['output'], 2),
            ];
        }

        $this->table(
            ['Model', 'Input (per 1M)', 'Output (per 1M)'],
            $rows
        );
    }
}
