<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewAdminNotification;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Throwable;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book)
    {
        $this->authorize('create', [Review::class, $book]);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['book_id'] = $book->id;

        // Check if user already reviewed this book
        $existingReview = Review::where('user_id', auth()->id())
            ->where('book_id', $book->id)
            ->first();

        if ($existingReview) {
            $before = $existingReview->only(['rating', 'comment']);
            $existingReview->update($validated);

            AuditLogger::log(
                action: 'review.updated',
                auditable: $existingReview,
                oldValues: $before,
                newValues: $existingReview->only(['rating', 'comment', 'book_id', 'user_id']),
                description: 'Customer updated a review.'
            );

            $message = 'Review updated successfully!';
        } else {
            $review = Review::create($validated);
            $message = 'Review submitted successfully!';

            AuditLogger::log(
                action: 'review.created',
                auditable: $review,
                newValues: $review->only(['rating', 'comment', 'book_id', 'user_id']),
                description: 'Customer submitted a new review.'
            );

            // Notify admins about the new review (non-blocking)
            try {
                $review->load(['user', 'book']);
                User::where('role', 'admin')->each(function ($admin) use ($review) {
                    $admin->notify(new NewReviewAdminNotification($review));
                });
            } catch (Throwable $e) {
                report($e);
                $message = 'Review submitted successfully, but admin notification email could not be sent.';
            }
        }

        return redirect()->route('books.show', $book)
            ->with('success', $message);
    }

    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $book = $review->book;
        $snapshot = $review->only(['id', 'user_id', 'book_id', 'rating', 'comment']);
        $review->delete();

        AuditLogger::log(
            action: 'review.deleted',
            oldValues: $snapshot,
            description: 'Review deleted by owner/admin.'
        );

        return redirect()->route('books.show', $book)
            ->with('success', 'Review deleted successfully!');
    }
}
