<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\BookIntelligenceService;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ModerateNewReviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public Review $review) {}

    public function handle(BookIntelligenceService $aiService): void
    {
        try {
            $moderationResult = $aiService->moderateReview($this->review->comment);

            if ($moderationResult['is_flagged']) {
                $this->review->update([
                    'is_flagged_by_ai' => true,
                    'ai_moderation_reason' => $moderationResult['reason']
                ]);

                // FIX: Use the correct static log() method from your custom AuditLogger
                AuditLogger::log(
                    'ai_moderation_quarantine',
                    $this->review,
                    [], 
                    [
                        'reason' => $moderationResult['reason'],
                        'book_id' => $this->review->book_id
                    ],
                    'AI automatically quarantined a toxic review.'
                );
            } else {
                // If it's clean, ensure the flag is removed (crucial if they edited a previously toxic review)
                $this->review->update([
                    'is_flagged_by_ai' => false,
                    'ai_moderation_reason' => null
                ]);
            }

            // ALWAYS recalculate the consensus after a review is processed or updated!
            $aiService->generateBookConsensus($this->review->book);

        } catch (\Exception $e) {
            Log::error("Review Moderation Job Failed: " . $e->getMessage());
        }
    }
}