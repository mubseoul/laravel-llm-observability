<?php

namespace Mubseoul\LLMObservability\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Mubseoul\LLMObservability\Models\LLMAlertRule;

class QuotaExceededNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected LLMAlertRule $alertRule
    ) {
    }

    public function via($notifiable): array
    {
        return $this->alertRule->notification_channels ?? ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('LLM Alert: ' . $this->alertRule->name)
            ->line('An LLM observability alert has been triggered.')
            ->line('Alert: ' . $this->alertRule->name)
            ->line('Type: ' . $this->alertRule->type)
            ->line('Description: ' . ($this->alertRule->description ?? 'N/A'))
            ->line('Triggered at: ' . now()->toDateTimeString())
            ->action('View Dashboard', url(config('llm-observability.dashboard.prefix', 'llm-observability')))
            ->line('Please review your LLM usage and adjust quotas if necessary.');
    }

    public function toArray($notifiable): array
    {
        return [
            'alert_rule_id' => $this->alertRule->id,
            'alert_name' => $this->alertRule->name,
            'alert_type' => $this->alertRule->type,
            'triggered_at' => now()->toIso8601String(),
        ];
    }
}
