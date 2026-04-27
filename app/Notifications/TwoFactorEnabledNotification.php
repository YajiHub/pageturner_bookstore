<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorEnabledNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Two-Factor Authentication Enabled - PageTurner')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Two-factor authentication has been enabled on your account.')
            ->line('You will now be required to enter a verification code sent to your email each time you log in.')
            ->line('If you did not enable this, please secure your account immediately.')
            ->action('Manage Profile', url('/profile'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Two-factor authentication was enabled on your account.',
        ];
    }
}
