<?php

namespace Vendor\LLMObservability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LLMRequest extends Model
{
    protected $fillable = [
        'request_id',
        'provider',
        'model',
        'user_id',
        'team_id',
        'api_key_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'input_chars',
        'output_chars',
        'latency_ms',
        'cost_usd',
        'status',
        'error_code',
        'error_message',
        'prompt_body',
        'response_body',
        'metadata',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'input_chars' => 'integer',
        'output_chars' => 'integer',
        'latency_ms' => 'integer',
        'cost_usd' => 'decimal:6',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('llm-observability.database.tables.requests', 'llm_requests');
        $this->connection = config('llm-observability.database.connection');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeForModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'error';
    }

    public function getEffectiveTokens(): int
    {
        return $this->total_tokens ?? 0;
    }
}
