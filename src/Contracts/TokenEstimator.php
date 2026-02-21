<?php

namespace Vendor\LLMObservability\Contracts;

interface TokenEstimator
{
    /**
     * Estimate token count from character count.
     */
    public function estimateTokens(int $characters): int;

    /**
     * Estimate token count from text.
     */
    public function estimateFromText(string $text): int;
}
