<?php

namespace Mubseoul\LLMObservability\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Mubseoul\LLMObservability\Models\LLMRequest;
use Mubseoul\LLMObservability\Models\LLMUsageAggregate;

class LLMDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'llm-observability::filament.pages.dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'LLM Observability';

    protected static ?int $navigationSort = 1;

    public function getStats(): array
    {
        $now = Carbon::now();
        $dayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(7);
        $monthStart = $now->copy()->startOfMonth();

        // Last 24 hours
        $last24h = LLMRequest::where('created_at', '>=', $dayStart)->get();

        // Last 7 days
        $last7d = LLMRequest::where('created_at', '>=', $weekStart)->get();

        // This month
        $thisMonth = LLMRequest::where('created_at', '>=', $monthStart)->get();

        return [
            'total_requests_24h' => $last24h->count(),
            'total_cost_24h' => $last24h->sum('cost_usd'),
            'avg_latency_24h' => $last24h->avg('latency_ms'),
            'error_rate_24h' => $last24h->count() > 0 ? ($last24h->where('status', 'error')->count() / $last24h->count()) * 100 : 0,

            'total_requests_7d' => $last7d->count(),
            'total_cost_7d' => $last7d->sum('cost_usd'),
            'total_tokens_7d' => $last7d->sum('total_tokens'),

            'total_requests_month' => $thisMonth->count(),
            'total_cost_month' => $thisMonth->sum('cost_usd'),
            'total_tokens_month' => $thisMonth->sum('total_tokens'),
        ];
    }
}
