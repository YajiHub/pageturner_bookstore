<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorDisabledNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Two-Factor Authentication Disabled - PageTurner')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Two-factor authentication has been disabled on your account.')
            ->line('Your account is now less secure. We recommend keeping 2FA enabled.')
            ->line('If you did not make this change, please secure your account immediately.')
            ->action('Manage Profile', url('/profile'));
    }
}
