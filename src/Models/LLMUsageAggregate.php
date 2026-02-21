<?php

namespace Vendor\LLMObservability\Models;

use Illuminate\Database\Eloquent\Model;

class LLMUsageAggregate extends Model
{
    protected $fillable = [
        'scope',
        'scope_id',
        'period',
        'period_start',
        'period_end',
        'provider',
        'model',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'total_tokens',
        'prompt_tokens',
        'completion_tokens',
        'total_cost_usd',
        'total_latency_ms',
        'avg_latency_ms',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_requests' => 'integer',
        'successful_requests' => 'integer',
        'failed_requests' => 'integer',
        'total_tokens' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_cost_usd' => 'decimal:6',
        'total_latency_ms' => 'integer',
        'avg_latency_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('llm-observability.database.tables.aggregates', 'llm_usage_aggregates');
        $this->connection = config('llm-observability.database.connection');
    }

    public function scopeForScope($query, string $scope, ?string $scopeId = null)
    {
        $query->where('scope', $scope);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        }

        return $query;
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeForModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    public function getErrorRate(): float
    {
        if ($this->total_requests === 0) {
            return 0.0;
        }

        return ($this->failed_requests / $this->total_requests) * 100;
    }

    public function getSuccessRate(): float
    {
        if ($this->total_requests === 0) {
            return 0.0;
        }

        return ($this->successful_requests / $this->total_requests) * 100;
    }
}
