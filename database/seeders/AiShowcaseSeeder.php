<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Seeding Realistic AI Reviews & Insights for Showcase...");

        $customers = User::where('role', 'customer')->take(5)->get();
        if ($customers->count() < 4) return;

        $showcaseBooks = Book::inRandomOrder()->take(5)->get();

        // Three distinct, realistic reviews to make the AI summary make sense
        $distinctReviews = [
            ['rating' => 5, 'comment' => "I absolutely loved this book! The English prose was beautiful and the story kept me hooked until the very end. Highly recommended."],
            ['rating' => 4, 'comment' => "A very solid read. The world-building is fantastic, though I felt the pacing in the middle chapters dragged just a little bit. Still a great experience."],
            ['rating' => 5, 'comment' => "An absolute masterpiece! The character development is top-notch and the climax left me speechless. 5 stars all the way."],
        ];

        foreach ($showcaseBooks as $book) {
            // 1. Create the 3 distinct Normal Reviews
            foreach ($distinctReviews as $index => $reviewData) {
                Review::create([
                    'user_id' => $customers[$index]->id,
                    'book_id' => $book->id,
                    'rating' => $reviewData['rating'],
                    'comment' => $reviewData['comment'],
                    'is_flagged_by_ai' => false,
                ]);
            }

            // 2. Create 1 Toxic Review (Hidden from normal users by the UI!)
            Review::create([
                'user_id' => $customers[3]->id,
                'book_id' => $book->id,
                'rating' => 1,
                'comment' => "This book is complete garbage. The author is an absolute idiot and I hate everything about this stupid website.",
                'is_flagged_by_ai' => true,
                'ai_moderation_reason' => 'AGGRESSIVE HARASSMENT / PROFANITY',
            ]);

            // 3. Inject an AI Consensus that perfectly matches the distinct reviews above
            DB::table('ai_book_insights')->insert([
                'book_id' => $book->id,
                'ai_summary' => "The overall consensus among readers is highly positive, reflecting a strong 4.6-star average. Customers frequently praise the beautiful prose, fantastic world-building, and gripping character development. While there was a minor note regarding slow pacing in the middle chapters, the majority consider it a highly recommended masterpiece.",
                'overall_sentiment' => 'Positive',
                'reviews_analyzed_count' => 3, // FIXED: Toxic reviews are ignored, so only 3 are analyzed!
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Mock an AI Usage Log
            DB::table('ai_usage_logs')->insert([
                'provider' => 'gemini',
                'feature' => 'review_summarization',
                'tokens_used' => rand(150, 300),
                'cost_estimate' => 0.00025,
                'input_prompt' => '[System mocked for Seeder Presentation]',
                'output_response' => '{"summary":"...","sentiment":"Positive"}',
                'was_fallback' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("AI Showcase Data successfully populated!");
    }
}