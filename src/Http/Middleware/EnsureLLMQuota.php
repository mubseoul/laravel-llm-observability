<?php

namespace Mubseoul\LLMObservability\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Mubseoul\LLMObservability\Services\QuotaEnforcer;

class EnsureLLMQuota
{
    public function __construct(
        protected QuotaEnforcer $quotaEnforcer
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Extract user/team context
        $userId = $request->user()?->id;
        $teamId = $request->user()?->currentTeam?->id ?? $request->user()?->team_id ?? null;
        $apiKeyId = $request->header('X-API-Key-ID');

        // Estimate tokens and cost if provided in request
        $estimatedTokens = (int) $request->header('X-Estimated-Tokens', 0);
        $estimatedCost = (float) $request->header('X-Estimated-Cost', 0.0);

        // Check quota
        $result = $this->quotaEnforcer->checkQuota(
            $userId,
            $teamId,
            $apiKeyId,
            $estimatedTokens,
            $estimatedCost
        );

        if (!$result['allowed']) {
            return response()->json([
                'error' => 'Quota exceeded',
                'message' => $result['reason'],
                'quota_type' => $result['quota_type'],
            ], 429);
        }

        return $next($request);
    }
}
