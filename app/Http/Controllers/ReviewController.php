<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewAdminNotification;
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
            $existingReview->update($validated);
            $message = 'Review updated successfully!';
        } else {
            $review = Review::create($validated);
            $message = 'Review submitted successfully!';

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
        $review->delete();

        return redirect()->route('books.show', $book)
            ->with('success', 'Review deleted successfully!');
    }
}
