<?php

namespace Mubseoul\LLMObservability\Services;

use Closure;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mubseoul\LLMObservability\Jobs\RecordLLMRequestJob;
use Mubseoul\LLMObservability\Models\LLMRequest;

class LLMRecorder
{
    protected array $context = [];

    public function __construct(
        protected CostCalculator $costCalculator,
        protected TokenEstimator $tokenEstimator
    ) {
    }

    /**
     * Record an LLM request and execute the callback.
     */
    public function record(array $payload): self
    {
        $this->context = $payload;

        return $this;
    }

    /**
     * Execute the LLM call and record the results.
     */
    public function send(Closure $callback): mixed
    {
        if (!$this->shouldRecord()) {
            return $callback();
        }

        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();

        try {
            $result = $callback();

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->recordRequest([
                'request_id' => $requestId,
                'status' => 'success',
                'latency_ms' => $latencyMs,
                'result' => $result,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->recordRequest([
                'request_id' => $requestId,
                'status' => 'error',
                'latency_ms' => $latencyMs,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record a request without executing a callback.
     */
    public function recordRequest(array $data): void
    {
        $payload = $this->preparePayload($data);

        if (config('llm-observability.recording.mode') === 'async') {
            $this->recordAsync($payload);
        } else {
            $this->recordSync($payload);
        }
    }

    protected function recordSync(array $payload): void
    {
        LLMRequest::create($payload);
        $this->updateAggregates($payload);
    }

    protected function recordAsync(array $payload): void
    {
        Queue::connection(config('llm-observability.recording.queue.connection'))
            ->pushOn(
                config('llm-observability.recording.queue.name', 'default'),
                new RecordLLMRequestJob($payload)
            );
    }

    protected function preparePayload(array $data): array
    {
        $provider = $this->context['provider'] ?? 'other';
        $model = $this->context['model'] ?? 'unknown';

        $promptTokens = $data['prompt_tokens']
            ?? $this->context['prompt_tokens']
            ?? $this->estimateTokensFromInput();

        $completionTokens = $data['completion_tokens']
            ?? $this->context['completion_tokens']
            ?? $this->estimateTokensFromOutput($data['result'] ?? null);

        $totalTokens = $data['total_tokens']
            ?? $this->context['total_tokens']
            ?? ($promptTokens + $completionTokens);

        $cost = $this->costCalculator->calculateCost(
            $provider,
            $model,
            $promptTokens,
            $completionTokens
        );

        $metadata = array_merge($this->context['metadata'] ?? [], [
            'app_name' => config('app.name'),
            'environment' => app()->environment(),
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'route' => request()?->route()?->getName(),
        ]);

        // Redact sensitive data
        $metadata = $this->redactSensitiveData($metadata);

        return [
            'request_id' => $data['request_id'] ?? Str::uuid()->toString(),
            'provider' => $provider,
            'model' => $model,
            'user_id' => $this->context['user_id'] ?? auth()->id(),
            'team_id' => $this->context['team_id'] ?? null,
            'api_key_id' => $this->context['api_key_id'] ?? null,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'input_chars' => $this->context['input_chars'] ?? null,
            'output_chars' => $this->context['output_chars'] ?? null,
            'latency_ms' => $data['latency_ms'] ?? null,
            'cost_usd' => $cost,
            'status' => $data['status'] ?? 'success',
            'error_code' => $data['error_code'] ?? null,
            'error_message' => $data['error_message'] ?? null,
            'prompt_body' => config('llm-observability.recording.store_bodies')
                ? ($this->context['prompt_body'] ?? null)
                : null,
            'response_body' => config('llm-observability.recording.store_bodies')
                ? ($data['response_body'] ?? null)
                : null,
            'metadata' => $metadata,
            'created_at' => now(),
        ];
    }

    protected function estimateTokensFromInput(): int
    {
        if (isset($this->context['input_chars'])) {
            return $this->tokenEstimator->estimateTokens($this->context['input_chars']);
        }

        if (isset($this->context['prompt_body'])) {
            return $this->tokenEstimator->estimateFromText($this->context['prompt_body']);
        }

        return 0;
    }

    protected function estimateTokensFromOutput($result): int
    {
        if (isset($this->context['output_chars'])) {
            return $this->tokenEstimator->estimateTokens($this->context['output_chars']);
        }

        if (is_string($result)) {
            return $this->tokenEstimator->estimateFromText($result);
        }

        return 0;
    }

    protected function redactSensitiveData(array $data): array
    {
        $patterns = config('llm-observability.recording.redact_patterns', []);

        $json = json_encode($data);

        foreach ($patterns as $pattern) {
            $json = preg_replace($pattern, '[REDACTED]', $json);
        }

        return json_decode($json, true);
    }

    protected function shouldRecord(): bool
    {
        if (!config('llm-observability.recording.enabled', true)) {
            return false;
        }

        $samplingRate = config('llm-observability.recording.sampling_rate', 1.0);

        return mt_rand() / mt_getrandmax() <= $samplingRate;
    }

    protected function updateAggregates(array $payload): void
    {
        $service = new AggregateService();
        $service->updateAggregatesForRequest($payload);
    }

    public function withUser(?int $userId): self
    {
        $this->context['user_id'] = $userId;

        return $this;
    }

    public function withTeam(?int $teamId): self
    {
        $this->context['team_id'] = $teamId;

        return $this;
    }

    public function withApiKey(?string $apiKeyId): self
    {
        $this->context['api_key_id'] = $apiKeyId;

        return $this;
    }

    public function withMetadata(array $metadata): self
    {
        $this->context['metadata'] = array_merge($this->context['metadata'] ?? [], $metadata);

        return $this;
    }
}
