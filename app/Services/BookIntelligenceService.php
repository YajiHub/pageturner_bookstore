<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookIntelligenceService
{
    public function __construct(protected AIServiceManager $aiManager) {}

    /**
     * Checks if a new review contains toxic or inappropriate content.
     */
    public function moderateReview(string $reviewText): array
    {
        //Explicitly allows harsh/negative reviews to protect free speech, 
        // while strictly blocking actual profanity and slurs.
        $prompt = "You are a content moderator for an online bookstore. Customers are fully allowed to leave extremely bad, harsh, or 1-star reviews if they hate a book, the website, or the author. DO NOT flag a review simply because it is negative, mean, or highly critical. 
        HOWEVER, if the review contains explicit severe profanity (e.g., f-words, s-words), racial/derogatory slurs, extreme hate speech against a protected group, or illegal explicit content, reply with 'FLAGGED: [Reason]'. 
        If the review is just a normal bad review (even if it uses words like 'garbage', 'idiot', or 'terrible') OR if it is a positive review, reply exactly with 'CLEAN'. 
        Review to analyze: \"{$reviewText}\"";

        try {
            $response = $this->aiManager->generateWithFallback($prompt, 'content_moderation');
            
            if (str_starts_with(strtoupper(trim($response)), 'FLAGGED')) {
                $reason = str_replace('FLAGGED:', '', strtoupper(trim($response)));
                return ['is_flagged' => true, 'reason' => trim($reason)];
            }
            return ['is_flagged' => false, 'reason' => null];
        } catch (\Exception $e) {
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
            ->where('is_flagged_by_ai', false) 
            ->pluck('comment')
            ->toArray();

        // Summarize even if there is just 1 review!
        if (count($reviews) < 1) {
            return; 
        }

        $reviewsToAnalyze = array_slice($reviews, 0, 20);
        $reviewsText = implode("\n- ", $reviewsToAnalyze);

        $prompt = "You are a literary analyst. Read these customer reviews for a book. Provide a JSON response with exactly two keys: 'summary' (a single, engaging 3-sentence paragraph summarizing the overall consensus of what readers liked and disliked) and 'sentiment' (exactly one of these words: Positive, Neutral, Negative, Mixed). Reviews:\n- {$reviewsText}";

        try {
            $response = $this->aiManager->generateWithFallback($prompt . "\n\nRETURN ONLY VALID JSON.", 'review_summarization');
            
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
            Log::error("Failed to generate AI insight for Book {$book->id}: " . $e->getMessage());
        }
    }
}