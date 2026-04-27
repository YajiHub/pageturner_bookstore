<?php

namespace App\Notifications;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuditCriticalAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AuditLog $auditLog)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Critical Audit Event Detected')
            ->greeting('Critical action detected')
            ->line('Action: '.$this->auditLog->action)
            ->line('Actor: '.($this->auditLog->user?->email ?? 'System'))
            ->line('Description: '.($this->auditLog->description ?? '-'))
            ->line('Timestamp: '.$this->auditLog->created_at?->format('Y-m-d H:i:s'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Critical audit event detected: '.$this->auditLog->action,
            'audit_log_id' => $this->auditLog->id,
            'action' => $this->auditLog->action,
            'description' => $this->auditLog->description,
            'created_at' => $this->auditLog->created_at?->toIso8601String(),
        ];
    }
}
