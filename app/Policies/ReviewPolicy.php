<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only verified purchasers can create reviews.
     * The user must have a completed order containing the book.
     */
    public function create(User $user, Book $book): bool
    {
        // Must have verified email
        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        return $user->orders()
            ->where('status', 'completed')
            ->whereHas('orderItems', function ($query) use ($book) {
                $query->where('book_id', $book->id);
            })->exists();
    }

    /**
     * Only the review owner or admin can delete a review.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $user->id === $review->user_id;
    }
}
