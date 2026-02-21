<?php

namespace Mubseoul\LLMObservability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LLMWebhookDelivery extends Model
{
    protected $fillable = [
        'alert_rule_id',
        'webhook_url',
        'payload',
        'status',
        'attempt',
        'response_code',
        'response_body',
        'error_message',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt' => 'integer',
        'response_code' => 'integer',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('llm-observability.database.tables.webhook_deliveries', 'llm_webhook_deliveries');
        $this->connection = config('llm-observability.database.connection');
    }

    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(LLMAlertRule::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markSuccess(int $responseCode, ?string $responseBody = null): void
    {
        $this->update([
            'status' => 'success',
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'delivered_at' => now(),
        ]);
    }

    public function markFailed(string $errorMessage, ?int $responseCode = null): void
    {
        $this->update([
            'status' => 'failed',
            'response_code' => $responseCode,
            'error_message' => $errorMessage,
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('attempt');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
