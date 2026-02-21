# Usage Examples

This document provides practical examples for integrating Laravel LLM Observability into your application.

## Table of Contents

- [Basic Usage](#basic-usage)
- [OpenAI Integration](#openai-integration)
- [Anthropic Integration](#anthropic-integration)
- [Custom Providers](#custom-providers)
- [Quota Management](#quota-management)
- [Alert Configuration](#alert-configuration)
- [Advanced Scenarios](#advanced-scenarios)

## Basic Usage

### Simple Request Recording

```php
use Vendor\LLMObservability\Facades\LLM;

$response = LLM::record([
    'provider' => 'openai',
    'model' => 'gpt-4',
])->send(function () {
    return 'Your LLM API call here';
});
```

### Recording with Full Context

```php
use Vendor\LLMObservability\Facades\LLM;

$response = LLM::record([
    'provider' => 'openai',
    'model' => 'gpt-4',
    'prompt_tokens' => 150,
    'completion_tokens' => 75,
])
->withUser(auth()->id())
->withTeam(auth()->user()->current_team_id)
->withMetadata([
    'feature' => 'chat',
    'conversation_id' => $conversationId,
    'session_id' => session()->getId(),
])
->send(function () {
    // Your LLM call
});
```

## OpenAI Integration

### Using OpenAI PHP SDK

```php
use OpenAI\Laravel\Facades\OpenAI;
use Vendor\LLMObservability\Facades\LLM;

class ChatService
{
    public function sendMessage(string $message): string
    {
        $response = LLM::record([
            'provider' => 'openai',
            'model' => 'gpt-4',
        ])
        ->withUser(auth()->id())
        ->send(function () use ($message) {
            return OpenAI::chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $message],
                ],
            ]);
        });

        // Extract and manually update tokens if needed
        if (isset($response->usage)) {
            LLM::recordRequest([
                'request_id' => $response->id,
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'total_tokens' => $response->usage->totalTokens,
            ]);
        }

        return $response->choices[0]->message->content;
    }
}
```

### Streaming Responses

```php
use OpenAI\Laravel\Facades\OpenAI;
use Vendor\LLMObservability\Facades\LLM;

public function streamChat(string $message)
{
    $startTime = microtime(true);
    $completionText = '';

    $stream = OpenAI::chat()->createStreamed([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'user', 'content' => $message],
        ],
    ]);

    foreach ($stream as $response) {
        $delta = $response->choices[0]->delta->content ?? '';
        $completionText .= $delta;
        echo $delta;
    }

    $latency = (int) ((microtime(true) - $startTime) * 1000);

    // Record after streaming completes
    LLM::record([
        'provider' => 'openai',
        'model' => 'gpt-4',
        'input_chars' => strlen($message),
        'output_chars' => strlen($completionText),
        'latency_ms' => $latency,
    ])->recordRequest([
        'status' => 'success',
    ]);
}
```

## Anthropic Integration

### Using Anthropic API

```php
use Illuminate\Support\Facades\Http;
use Vendor\LLMObservability\Facades\LLM;

class AnthropicService
{
    public function sendMessage(string $message): array
    {
        $response = LLM::record([
            'provider' => 'anthropic',
            'model' => 'claude-3-sonnet-20240229',
        ])
        ->withUser(auth()->id())
        ->send(function () use ($message) {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-sonnet-20240229',
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $message],
                ],
            ]);

            return $response->json();
        });

        // Extract token usage from response
        if (isset($response['usage'])) {
            LLM::recordRequest([
                'prompt_tokens' => $response['usage']['input_tokens'],
                'completion_tokens' => $response['usage']['output_tokens'],
            ]);
        }

        return $response;
    }
}
```

## Custom Providers

### Ollama Integration

```php
use Illuminate\Support\Facades\Http;
use Vendor\LLMObservability\Facades\LLM;

class OllamaService
{
    public function generate(string $prompt, string $model = 'llama2'): string
    {
        $response = LLM::record([
            'provider' => 'ollama',
            'model' => $model,
            'input_chars' => strlen($prompt),
        ])
        ->send(function () use ($model, $prompt) {
            $response = Http::post('http://localhost:11434/api/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ]);

            return $response->json();
        });

        // Ollama doesn't provide token counts, use character estimation
        LLM::recordRequest([
            'output_chars' => strlen($response['response']),
        ]);

        return $response['response'];
    }
}
```

## Quota Management

### Creating User Quotas

```php
use Vendor\LLMObservability\Models\LLMQuota;

// Create a quota for a specific user
LLMQuota::create([
    'scope' => 'user',
    'scope_id' => (string) $userId,
    'requests_per_day' => 1000,
    'requests_per_month' => 10000,
    'tokens_per_day' => 1000000,
    'cost_per_day' => 10.00,
    'cost_per_month' => 100.00,
    'enabled' => true,
]);
```

### Creating Team Quotas

```php
use Vendor\LLMObservability\Models\LLMQuota;

LLMQuota::create([
    'scope' => 'team',
    'scope_id' => (string) $teamId,
    'requests_per_month' => 50000,
    'cost_per_month' => 500.00,
    'enabled' => true,
]);
```

### Programmatic Quota Checking

```php
use Vendor\LLMObservability\Services\QuotaEnforcer;

$enforcer = app(QuotaEnforcer::class);

$result = $enforcer->checkQuota(
    userId: auth()->id(),
    estimatedTokens: 1000,
    estimatedCost: 0.05
);

if (!$result['allowed']) {
    return response()->json([
        'error' => $result['reason'],
        'quota_type' => $result['quota_type'],
    ], 429);
}

// Proceed with LLM call
```

### Middleware Protection

```php
// In routes/api.php
Route::middleware(['auth', 'llm.quota'])->group(function () {
    Route::post('/api/chat', [ChatController::class, 'send']);
    Route::post('/api/completion', [CompletionController::class, 'create']);
});
```

## Alert Configuration

### Cost Threshold Alert

```php
use Vendor\LLMObservability\Models\LLMAlertRule;

LLMAlertRule::create([
    'name' => 'Daily Cost Limit',
    'description' => 'Alert when daily costs exceed $100',
    'type' => 'cost_threshold',
    'scope' => 'global',
    'threshold_config' => [
        'cost_usd' => 100.00,
        'period' => 'day',
    ],
    'send_notification' => true,
    'notification_channels' => ['mail', 'slack'],
    'send_webhook' => true,
    'webhook_url' => 'https://your-app.com/webhooks/llm-alert',
    'enabled' => true,
]);
```

### Error Rate Alert

```php
LLMAlertRule::create([
    'name' => 'High Error Rate',
    'description' => 'Alert when error rate exceeds 5%',
    'type' => 'error_rate',
    'scope' => 'global',
    'threshold_config' => [
        'error_rate_percent' => 5.0,
        'period' => 'day',
    ],
    'send_notification' => true,
    'enabled' => true,
]);
```

### User-Specific Alert

```php
LLMAlertRule::create([
    'name' => 'User Cost Alert',
    'description' => 'Alert when user exceeds daily budget',
    'type' => 'cost_threshold',
    'scope' => 'user',
    'scope_id' => (string) $userId,
    'threshold_config' => [
        'cost_usd' => 10.00,
        'period' => 'day',
    ],
    'send_notification' => true,
    'enabled' => true,
]);
```

## Advanced Scenarios

### Multi-Provider Fallback

```php
use Vendor\LLMObservability\Facades\LLM;

class AIService
{
    public function generateWithFallback(string $prompt): string
    {
        try {
            return $this->generateWithOpenAI($prompt);
        } catch (\Exception $e) {
            return $this->generateWithAnthropic($prompt);
        }
    }

    private function generateWithOpenAI(string $prompt): string
    {
        return LLM::record([
            'provider' => 'openai',
            'model' => 'gpt-4',
        ])->send(function () use ($prompt) {
            // OpenAI call
        });
    }

    private function generateWithAnthropic(string $prompt): string
    {
        return LLM::record([
            'provider' => 'anthropic',
            'model' => 'claude-3-sonnet-20240229',
        ])->send(function () use ($prompt) {
            // Anthropic call
        });
    }
}
```

### Batch Processing with Rate Limiting

```php
use Vendor\LLMObservability\Facades\LLM;
use Vendor\LLMObservability\Services\QuotaEnforcer;

class BatchProcessor
{
    public function processItems(array $items): array
    {
        $results = [];
        $enforcer = app(QuotaEnforcer::class);

        foreach ($items as $item) {
            // Check quota before each request
            $quotaCheck = $enforcer->checkQuota(
                userId: auth()->id(),
                estimatedTokens: 500
            );

            if (!$quotaCheck['allowed']) {
                $results[] = [
                    'item' => $item,
                    'error' => 'Quota exceeded: ' . $quotaCheck['reason'],
                ];
                continue;
            }

            try {
                $result = LLM::record([
                    'provider' => 'openai',
                    'model' => 'gpt-4',
                ])
                ->withMetadata(['batch_id' => $batchId])
                ->send(function () use ($item) {
                    // Process item
                });

                $results[] = ['item' => $item, 'result' => $result];
            } catch (\Exception $e) {
                $results[] = ['item' => $item, 'error' => $e->getMessage()];
            }

            // Rate limiting
            usleep(200000); // 200ms delay between requests
        }

        return $results;
    }
}
```

### Custom Pricing Provider

```php
use Vendor\LLMObservability\Contracts\PricingProvider;

class CustomPricingProvider implements PricingProvider
{
    public function getPricing(string $provider, string $model): ?array
    {
        // Fetch pricing from your database or external API
        $pricing = DB::table('custom_llm_pricing')
            ->where('provider', $provider)
            ->where('model', $model)
            ->first();

        if (!$pricing) {
            return null;
        }

        return [
            'input' => $pricing->input_price_per_million,
            'output' => $pricing->output_price_per_million,
        ];
    }

    public function calculateCost(
        string $provider,
        string $model,
        int $promptTokens,
        int $completionTokens
    ): float {
        $pricing = $this->getPricing($provider, $model);

        if (!$pricing) {
            return 0.0;
        }

        $inputCost = ($promptTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($completionTokens / 1_000_000) * $pricing['output'];

        return round($inputCost + $outputCost, 6);
    }
}

// Register in config/llm-observability.php
'pricing_provider' => \App\Services\CustomPricingProvider::class,
```

### Analytics Dashboard Integration

```php
use Vendor\LLMObservability\Models\LLMRequest;
use Vendor\LLMObservability\Models\LLMUsageAggregate;

class LLMAnalytics
{
    public function getCostByProvider(string $period = 'month'): array
    {
        $start = $period === 'month'
            ? now()->startOfMonth()
            : now()->subDays(7);

        return LLMRequest::where('created_at', '>=', $start)
            ->groupBy('provider')
            ->selectRaw('provider, SUM(cost_usd) as total_cost')
            ->pluck('total_cost', 'provider')
            ->toArray();
    }

    public function getTopUsers(int $limit = 10): array
    {
        return LLMRequest::where('created_at', '>=', now()->startOfMonth())
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as requests, SUM(cost_usd) as cost')
            ->orderByDesc('cost')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getErrorAnalysis(): array
    {
        $total = LLMRequest::where('created_at', '>=', now()->subDays(7))->count();
        $errors = LLMRequest::where('created_at', '>=', now()->subDays(7))
            ->where('status', 'error')
            ->groupBy('error_code')
            ->selectRaw('error_code, COUNT(*) as count')
            ->get();

        return [
            'total_requests' => $total,
            'error_breakdown' => $errors,
            'error_rate' => $total > 0 ? ($errors->sum('count') / $total) * 100 : 0,
        ];
    }
}
```

## Best Practices

1. **Always use async mode in production** for better performance
2. **Enable sampling** for extremely high-volume applications
3. **Set appropriate quotas** to prevent unexpected costs
4. **Configure alerts** for cost thresholds and error rates
5. **Review pricing regularly** as providers update their rates
6. **Use metadata** to track features, sessions, or experiments
7. **Monitor the dashboard** regularly for anomalies
8. **Prune old logs** to keep database size manageable
