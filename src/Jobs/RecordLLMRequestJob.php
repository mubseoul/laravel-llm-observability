<?php

namespace Vendor\LLMObservability\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Vendor\LLMObservability\Models\LLMRequest;
use Vendor\LLMObservability\Services\AggregateService;

class RecordLLMRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $payload
    ) {
    }

    public function handle(): void
    {
        LLMRequest::create($this->payload);

        $aggregateService = new AggregateService();
        $aggregateService->updateAggregatesForRequest($this->payload);
    }

    public function tags(): array
    {
        return [
            'llm-observability',
            'provider:' . ($this->payload['provider'] ?? 'unknown'),
            'model:' . ($this->payload['model'] ?? 'unknown'),
        ];
    }
}
