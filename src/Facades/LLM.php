<?php

namespace Mubseoul\LLMObservability\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Mubseoul\LLMObservability\Services\LLMRecorder record(array $payload)
 * @method static \Mubseoul\LLMObservability\Services\LLMRecorder withUser(?int $userId)
 * @method static \Mubseoul\LLMObservability\Services\LLMRecorder withTeam(?int $teamId)
 * @method static \Mubseoul\LLMObservability\Services\LLMRecorder withApiKey(?string $apiKeyId)
 * @method static \Mubseoul\LLMObservability\Services\LLMRecorder withMetadata(array $metadata)
 * @method static mixed send(\Closure $callback)
 * @method static void recordRequest(array $data)
 *
 * @see \Mubseoul\LLMObservability\Services\LLMRecorder
 */
class LLM extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'llm-recorder';
    }
}
