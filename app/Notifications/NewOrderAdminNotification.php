<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderAdminNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Order #'.$this->order->id.' Received')
            ->greeting('Hello Admin!')
            ->line('A new order has been placed by '.$this->order->user->name.'.')
            ->line('Order #'.$this->order->id)
            ->line('Total Amount: ₱'.number_format($this->order->total_amount, 2))
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('Please review and process this order.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => 'New order #'.$this->order->id.' placed by '.$this->order->user->name,
            'total_amount' => $this->order->total_amount,
            'customer_name' => $this->order->user->name,
        ];
    }
}
