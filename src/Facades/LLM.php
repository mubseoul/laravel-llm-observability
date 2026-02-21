<?php

namespace Vendor\LLMObservability\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Vendor\LLMObservability\Services\LLMRecorder record(array $payload)
 * @method static \Vendor\LLMObservability\Services\LLMRecorder withUser(?int $userId)
 * @method static \Vendor\LLMObservability\Services\LLMRecorder withTeam(?int $teamId)
 * @method static \Vendor\LLMObservability\Services\LLMRecorder withApiKey(?string $apiKeyId)
 * @method static \Vendor\LLMObservability\Services\LLMRecorder withMetadata(array $metadata)
 * @method static mixed send(\Closure $callback)
 * @method static void recordRequest(array $data)
 *
 * @see \Vendor\LLMObservability\Services\LLMRecorder
 */
class LLM extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'llm-recorder';
    }
}
