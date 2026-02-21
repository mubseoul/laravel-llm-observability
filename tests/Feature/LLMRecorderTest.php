<?php

use Illuminate\Support\Facades\Queue;
use Vendor\LLMObservability\Jobs\RecordLLMRequestJob;
use Vendor\LLMObservability\Models\LLMRequest;
use Vendor\LLMObservability\Services\LLMRecorder;

beforeEach(function () {
    config(['llm-observability.recording.enabled' => true]);
    config(['llm-observability.recording.mode' => 'sync']);
});

it('can record a successful LLM request', function () {
    $recorder = app(LLMRecorder::class);

    $result = $recorder->record([
        'provider' => 'openai',
        'model' => 'gpt-4',
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
    ])->send(function () {
        return 'Test response';
    });

    expect($result)->toBe('Test response')
        ->and(LLMRequest::count())->toBe(1);

    $request = LLMRequest::first();
    expect($request->provider)->toBe('openai')
        ->and($request->model)->toBe('gpt-4')
        ->and($request->status)->toBe('success')
        ->and($request->prompt_tokens)->toBe(100)
        ->and($request->completion_tokens)->toBe(50);
});

it('can record a failed LLM request', function () {
    $recorder = app(LLMRecorder::class);

    try {
        $recorder->record([
            'provider' => 'openai',
            'model' => 'gpt-4',
        ])->send(function () {
            throw new \Exception('API Error');
        });
    } catch (\Exception $e) {
        // Expected exception
    }

    expect(LLMRequest::count())->toBe(1);

    $request = LLMRequest::first();
    expect($request->status)->toBe('error')
        ->and($request->error_message)->toContain('API Error');
});

it('queues recording when in async mode', function () {
    Queue::fake();

    config(['llm-observability.recording.mode' => 'async']);

    $recorder = app(LLMRecorder::class);

    $recorder->record([
        'provider' => 'openai',
        'model' => 'gpt-4',
    ])->send(function () {
        return 'Test response';
    });

    Queue::assertPushed(RecordLLMRequestJob::class);
});

it('respects sampling rate', function () {
    config(['llm-observability.recording.sampling_rate' => 0.0]);

    $recorder = app(LLMRecorder::class);

    $recorder->record([
        'provider' => 'openai',
        'model' => 'gpt-4',
    ])->send(function () {
        return 'Test response';
    });

    expect(LLMRequest::count())->toBe(0);
});

it('can set user context', function () {
    $recorder = app(LLMRecorder::class);

    $recorder->record([
        'provider' => 'openai',
        'model' => 'gpt-4',
    ])->withUser(123)->send(function () {
        return 'Test response';
    });

    $request = LLMRequest::first();
    expect($request->user_id)->toBe(123);
});

it('calculates cost automatically', function () {
    $recorder = app(LLMRecorder::class);

    $recorder->record([
        'provider' => 'openai',
        'model' => 'gpt-4',
        'prompt_tokens' => 1000,
        'completion_tokens' => 500,
    ])->send(function () {
        return 'Test response';
    });

    $request = LLMRequest::first();
    expect($request->cost_usd)->toBeGreaterThan(0);
});
