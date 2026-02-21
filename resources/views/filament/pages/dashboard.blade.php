<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Overview --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            @php
                $stats = $this->getStats();
            @endphp

            {{-- Last 24 Hours --}}
            <x-filament::card>
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Last 24 Hours</h3>
                    <div class="text-3xl font-bold">{{ number_format($stats['total_requests_24h']) }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Requests</p>
                    <div class="mt-2 space-y-1">
                        <p class="text-xs text-gray-500">
                            Cost: ${{ number_format($stats['total_cost_24h'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Avg Latency: {{ number_format($stats['avg_latency_24h']) }}ms
                        </p>
                        <p class="text-xs text-gray-500">
                            Error Rate: {{ number_format($stats['error_rate_24h'], 1) }}%
                        </p>
                    </div>
                </div>
            </x-filament::card>

            {{-- Last 7 Days --}}
            <x-filament::card>
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Last 7 Days</h3>
                    <div class="text-3xl font-bold">{{ number_format($stats['total_requests_7d']) }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Requests</p>
                    <div class="mt-2 space-y-1">
                        <p class="text-xs text-gray-500">
                            Cost: ${{ number_format($stats['total_cost_7d'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Tokens: {{ number_format($stats['total_tokens_7d']) }}
                        </p>
                    </div>
                </div>
            </x-filament::card>

            {{-- This Month --}}
            <x-filament::card>
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">This Month</h3>
                    <div class="text-3xl font-bold">{{ number_format($stats['total_requests_month']) }}</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Requests</p>
                    <div class="mt-2 space-y-1">
                        <p class="text-xs text-gray-500">
                            Cost: ${{ number_format($stats['total_cost_month'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Tokens: {{ number_format($stats['total_tokens_month']) }}
                        </p>
                    </div>
                </div>
            </x-filament::card>

            {{-- Quick Actions --}}
            <x-filament::card>
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Quick Actions</h3>
                    <div class="space-y-2 mt-4">
                        <a href="{{ url('llm-observability/llm-requests') }}"
                           class="block text-sm text-primary-600 hover:text-primary-500">
                            View All Requests →
                        </a>
                        <a href="#"
                           class="block text-sm text-primary-600 hover:text-primary-500">
                            Configure Quotas →
                        </a>
                        <a href="#"
                           class="block text-sm text-primary-600 hover:text-primary-500">
                            Manage Alerts →
                        </a>
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- Additional Information --}}
        <x-filament::card>
            <div class="prose dark:prose-invert max-w-none">
                <h3>LLM Observability Dashboard</h3>
                <p>
                    Monitor your LLM usage, costs, and performance in real-time. This dashboard provides
                    comprehensive insights into your AI infrastructure.
                </p>
                <ul>
                    <li>Track requests across all LLM providers (OpenAI, Anthropic, etc.)</li>
                    <li>Monitor costs and enforce budgets</li>
                    <li>Analyze performance metrics and error rates</li>
                    <li>Set up alerts and quotas</li>
                </ul>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
