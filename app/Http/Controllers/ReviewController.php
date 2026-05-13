<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewAdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    /**
     * Store a newly created review or update an existing one.
     */
    public function store(Request $request, Book $book)
    {
        // Authorization: Only admins or users who purchased the book can review
        $hasPurchased = auth()->user()->orders()
            ->where('status', 'completed')
            ->whereHas('orderItems', function ($query) use ($book) {
                $query->where('book_id', $book->id);
            })->exists();

        if (!auth()->user()->isAdmin() && !$hasPurchased) {
            return back()->with('error', 'You must purchase this book to leave a review.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // 1. Create or Update the Review FIRST
        // If the user already reviewed the book, this safely overwrites their old review.
        $review = Review::updateOrCreate(
            [
                // Search parameters
                'user_id' => auth()->id(),
                'book_id' => $book->id,
            ],
            [
                // Values to update or insert
                'rating' => $request->rating,
                'comment' => $request->comment,
                
                // Reset AI moderation flags so the new/edited text gets reviewed freshly
                'is_flagged_by_ai' => false,
                'ai_moderation_reason' => null,
            ]
        );

        // 2. Send email notification to admins using the freshly created $review object
        $admins = User::where('role', 'admin')->get();
        if ($admins->isNotEmpty()) {
            try {
                Notification::send($admins, new NewReviewAdminNotification($review));
            } catch (\Exception $e) {
                report($e);
            }
        }

        // 3. LAB 8: Dispatch AI task to background queue for moderation and summarization
        if (class_exists(\App\Jobs\ModerateNewReviewJob::class)) {
            \App\Jobs\ModerateNewReviewJob::dispatch($review);
        }

        return back()->with('success', 'Review submitted. It will be visible shortly after automated review.');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(Review $review)
    {
        // Simple authorization check
        if (auth()->user()->role !== 'admin' && auth()->id() !== $review->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $book = $review->book;
        $review->delete();

        // LAB 8: If a review is deleted, recalculate the AI Book Consensus
        if (class_exists(\App\Services\BookIntelligenceService::class)) {
            try {
                $aiService = app(\App\Services\BookIntelligenceService::class);
                $aiService->generateBookConsensus($book);
            } catch (\Exception $e) {
                Log::error("Failed to recalculate AI consensus on review delete: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Review deleted successfully.');
    }


    public function overrideAiModeration(Review $review)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $oldReason = $review->ai_moderation_reason;

        $review->update([
            'is_flagged_by_ai' => false,
            'ai_moderation_reason' => null,
        ]);

        // Audit the manual override using the correct static method
        \App\Services\AuditLogger::log(
            'admin_overrode_ai_moderation',
            $review,
            ['previous_reason' => $oldReason],
            [],
            'Admin manually overrode AI quarantine and restored review.'
        );

        // Recalculate consensus since the review is now active again
        app(\App\Services\BookIntelligenceService::class)->generateBookConsensus($review->book);

        return back()->with('success', 'AI Moderation manually overridden. Review is now public.');
    }
    
}