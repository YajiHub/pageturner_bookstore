<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookCatalogUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $action,
        private readonly string $bookTitle,
        private readonly ?int $bookId,
        private readonly string $actorName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subjectAction = ucfirst($this->action);
        $message = "Book '{$this->bookTitle}' was {$this->action} by {$this->actorName}.";

        $mail = (new MailMessage)
            ->subject("Book {$subjectAction} - {$this->bookTitle}")
            ->greeting('Hello Admin!')
            ->line($message);

        if ($this->bookId) {
            $mail->action('View Book', url('/books/'.$this->bookId));
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Book '{$this->bookTitle}' was {$this->action} by {$this->actorName}.",
            'action' => $this->action,
            'book_title' => $this->bookTitle,
            'book_id' => $this->bookId,
            'actor_name' => $this->actorName,
        ];
    }
}