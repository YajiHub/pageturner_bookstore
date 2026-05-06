<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class BookIntelligenceService
{
    public function __construct(protected AIServiceManager $aiManager) {}

    /**
     * Checks if a new review contains toxic or inappropriate content.
     */
    public function moderateReview(string $reviewText): array
    {
        $prompt = "You are a strict content moderator for a family-friendly bookstore. Analyze the following review. If it contains profanity, hate speech, explicit content, or aggressive harassment, reply with 'FLAGGED: [Reason]'. If it is acceptable (even if it's a negative review of the book), reply with 'CLEAN'. Review: \"{$reviewText}\"";

        try {
            $response = $this->aiManager->generateWithFallback($prompt, 'content_moderation');
            
            if (str_starts_with(strtoupper(trim($response)), 'FLAGGED')) {
                $reason = str_replace('FLAGGED:', '', strtoupper(trim($response)));
                return ['is_flagged' => true, 'reason' => trim($reason)];
            }
            return ['is_flagged' => false, 'reason' => null];
        } catch (\Exception $e) {
            // Fail open: don't block user reviews if AI is down
            return ['is_flagged' => false, 'reason' => 'AI System Unavailable'];
        }
    }

    /**
     * Summarizes all reviews for a book into one consensus paragraph.
     */
    public function generateBookConsensus(Book $book): void
    {
        $reviews = DB::table('reviews')
            ->where('book_id', $book->id)
            ->where('is_flagged_by_ai', false) // Don't include toxic reviews
            ->pluck('comment')
            ->toArray();

        if (count($reviews) < 3) {
            return; // Not enough data to summarize
        }

        // Limit to recent 20 reviews to avoid token limits
        $reviewsToAnalyze = array_slice($reviews, 0, 20);
        $reviewsText = implode("\n- ", $reviewsToAnalyze);

        $prompt = "You are a literary analyst. Read these customer reviews for a book. Provide a JSON response with exactly two keys: 'summary' (a single, engaging 3-sentence paragraph summarizing the overall consensus of what readers liked and disliked) and 'sentiment' (exactly one of these words: Positive, Neutral, Negative, Mixed). Reviews:\n- {$reviewsText}";

        try {
            // Instruct Gemini/Ollama to return JSON
            $response = $this->aiManager->generateWithFallback($prompt . "\n\nRETURN ONLY VALID JSON.", 'review_summarization');
            
            // Clean markdown JSON formatting if present
            $jsonString = str_replace(['```json', '```'], '', $response);
            $data = json_decode(trim($jsonString), true);

            if (isset($data['summary']) && isset($data['sentiment'])) {
                DB::table('ai_book_insights')->updateOrInsert(
                    ['book_id' => $book->id],
                    [
                        'ai_summary' => $data['summary'],
                        'overall_sentiment' => $data['sentiment'],
                        'reviews_analyzed_count' => count($reviews),
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            \Log::error("Failed to generate AI insight for Book {$book->id}: " . $e->getMessage());
        }
    }
}