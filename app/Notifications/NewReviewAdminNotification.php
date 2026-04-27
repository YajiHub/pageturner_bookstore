<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewAdminNotification extends Notification
{
    use Queueable;

    public function __construct(public Review $review) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Review Submitted for "'.$this->review->book->title.'"')
            ->greeting('Hello Admin!')
            ->line($this->review->user->name.' submitted a review for "'.$this->review->book->title.'".')
            ->line('Rating: '.$this->review->rating.'/5')
            ->line('Comment: '.($this->review->comment ?: 'No comment'))
            ->action('View Book', url('/books/'.$this->review->book_id))
            ->line('Please review this submission.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'message' => $this->review->user->name.' reviewed "'.$this->review->book->title.'" ('.$this->review->rating.'/5)',
            'book_title' => $this->review->book->title,
            'reviewer_name' => $this->review->user->name,
            'rating' => $this->review->rating,
        ];
    }
}
