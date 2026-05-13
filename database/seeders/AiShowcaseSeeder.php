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
        $this->command->info("Seeding AI Reviews & Insights for Showcase...");

        $customers = User::where('role', 'customer')->take(5)->get();
        if ($customers->count() < 3) return;

        // Pick 5 random books to be our "Showcase" books
        $showcaseBooks = Book::inRandomOrder()->take(5)->get();

        foreach ($showcaseBooks as $book) {
            // 1. Create 3 Good/Normal Reviews
            foreach ($customers->take(3) as $index => $customer) {
                Review::create([
                    'user_id' => $customer->id,
                    'book_id' => $book->id,
                    'rating' => rand(4, 5),
                    'comment' => "I absolutely loved this book! The English prose was beautiful and the story kept me hooked until the very end. Highly recommended.",
                    'is_flagged_by_ai' => false,
                ]);
            }

            // 2. Create 1 Toxic Review (Hidden by AI)
            Review::create([
                'user_id' => $customers->last()->id,
                'book_id' => $book->id,
                'rating' => 1,
                'comment' => "This book is complete garbage. The author is an absolute idiot and I hate everything about this stupid website.",
                'is_flagged_by_ai' => true,
                'ai_moderation_reason' => 'AGGRESSIVE HARASSMENT / PROFANITY',
            ]);

            // 3. Directly inject an AI Consensus so the UI displays immediately
            DB::table('ai_book_insights')->insert([
                'book_id' => $book->id,
                'ai_summary' => "The overall consensus among readers is overwhelmingly positive. Customers frequently praise the beautiful prose and gripping storyline that keeps them engaged. While there was a minor detractor regarding the pacing, the majority highly recommend this masterpiece.",
                'overall_sentiment' => 'Positive',
                'reviews_analyzed_count' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Mock an AI Usage Log for the Audit Trail
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