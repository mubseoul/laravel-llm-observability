<?php

namespace Vendor\LLMObservability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LLMAlertRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'scope',
        'scope_id',
        'threshold_config',
        'send_notification',
        'notification_channels',
        'send_webhook',
        'webhook_url',
        'enabled',
        'last_triggered_at',
    ];

    protected $casts = [
        'threshold_config' => 'array',
        'notification_channels' => 'array',
        'send_notification' => 'boolean',
        'send_webhook' => 'boolean',
        'enabled' => 'boolean',
        'last_triggered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('llm-observability.database.tables.alert_rules', 'llm_alert_rules');
        $this->connection = config('llm-observability.database.connection');
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(LLMWebhookDelivery::class, 'alert_rule_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForScope($query, string $scope, ?string $scopeId = null)
    {
        $query->where('scope', $scope);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        }

        return $query;
    }

    public function markTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    public function shouldNotify(): bool
    {
        return $this->enabled && $this->send_notification;
    }

    public function shouldSendWebhook(): bool
    {
        return $this->enabled && $this->send_webhook && !empty($this->webhook_url);
    }

    public function getThreshold(string $key, mixed $default = null): mixed
    {
        return $this->threshold_config[$key] ?? $default;
    }
}
