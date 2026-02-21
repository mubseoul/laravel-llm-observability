<?php

namespace Mubseoul\LLMObservability\Services;

use Mubseoul\LLMObservability\Contracts\TokenEstimator as TokenEstimatorContract;

class TokenEstimator implements TokenEstimatorContract
{
    public function estimateTokens(int $characters): int
    {
        $charsPerToken = config('llm-observability.token_estimation.chars_per_token', 4);

        return (int) ceil($characters / $charsPerToken);
    }

    public function estimateFromText(string $text): int
    {
        return $this->estimateTokens(mb_strlen($text));
    }
}
