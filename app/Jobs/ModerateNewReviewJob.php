<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\BookIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ModerateNewReviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Review $review) {}

    public function handle(BookIntelligenceService $aiService): void
    {
        if (empty($this->review->comment)) return;

        // 1. Moderate the text
        $moderation = $aiService->moderateReview($this->review->comment);
        
        if ($moderation['is_flagged']) {
            $this->review->update([
                'is_flagged_by_ai' => true,
                'ai_moderation_reason' => $moderation['reason']
            ]);
        }

        // 2. Recalculate the book's AI consensus summary now that a new review exists
        $aiService->generateBookConsensus($this->review->book);
    }
}