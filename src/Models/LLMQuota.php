<?php

namespace Vendor\LLMObservability\Models;

use Illuminate\Database\Eloquent\Model;

class LLMQuota extends Model
{
    protected $fillable = [
        'scope',
        'scope_id',
        'requests_per_day',
        'requests_per_month',
        'tokens_per_day',
        'tokens_per_month',
        'cost_per_day',
        'cost_per_month',
        'enabled',
    ];

    protected $casts = [
        'requests_per_day' => 'integer',
        'requests_per_month' => 'integer',
        'tokens_per_day' => 'integer',
        'tokens_per_month' => 'integer',
        'cost_per_day' => 'decimal:2',
        'cost_per_month' => 'decimal:2',
        'enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('llm-observability.database.tables.quotas', 'llm_quotas');
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

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function hasRequestsPerDayLimit(): bool
    {
        return $this->requests_per_day !== null;
    }

    public function hasRequestsPerMonthLimit(): bool
    {
        return $this->requests_per_month !== null;
    }

    public function hasTokensPerDayLimit(): bool
    {
        return $this->tokens_per_day !== null;
    }

    public function hasTokensPerMonthLimit(): bool
    {
        return $this->tokens_per_month !== null;
    }

    public function hasCostPerDayLimit(): bool
    {
        return $this->cost_per_day !== null;
    }

    public function hasCostPerMonthLimit(): bool
    {
        return $this->cost_per_month !== null;
    }

    public function hasAnyLimit(): bool
    {
        return $this->hasRequestsPerDayLimit()
            || $this->hasRequestsPerMonthLimit()
            || $this->hasTokensPerDayLimit()
            || $this->hasTokensPerMonthLimit()
            || $this->hasCostPerDayLimit()
            || $this->hasCostPerMonthLimit();
    }
}
