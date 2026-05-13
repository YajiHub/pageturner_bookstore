<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Comprehensive review collection designed for AI sentiment analysis demonstration (LAB 8)
     * Includes diverse sentiment levels, writing styles, and topics for robust AI training
     */
    private array $reviews = [
        // 5-STAR REVIEWS - EXTREMELY POSITIVE
        [
            'rating' => 5,
            'comment' => 'Absolutely outstanding! This is easily one of the best books I have ever read. The prose is beautiful, the characters are incredibly well-developed, and I found myself completely unable to put it down. Every page kept me engaged and eager to read more. The author has created a masterpiece that deserves to be read by everyone. Highly recommended!',
        ],
        [
            'rating' => 5,
            'comment' => 'Five stars does not do this book justice! The storytelling is phenomenal, the plot twists are perfectly executed, and the emotional depth left me in tears multiple times. This is a rare gem in the literary world. I have already purchased copies for all my friends.',
        ],
        [
            'rating' => 5,
            'comment' => 'Brilliant, captivating, and transformative! This book changed my perspective on life. The author has an exceptional gift for storytelling. I finished it in two days because I could not stop reading. An absolute triumph!',
        ],
        [
            'rating' => 5,
            'comment' => 'This book is a work of art. Every sentence is carefully crafted, every character is authentic, and every moment matters. I have read it three times and discover something new each time. If you love quality literature, this is essential reading.',
        ],
        
        // 4-STAR REVIEWS - POSITIVE
        [
            'rating' => 4,
            'comment' => 'Very good book with excellent pacing and interesting characters. The plot keeps you engaged throughout. Some parts could have been developed further, but overall it is a solid, entertaining read that I would recommend to most readers.',
        ],
        [
            'rating' => 4,
            'comment' => 'Great storytelling and wonderful character development. The world-building is immersive and believable. A few plot points felt slightly rushed towards the end, but it did not detract from the overall quality of the work.',
        ],
        [
            'rating' => 4,
            'comment' => 'Highly enjoyable with a compelling narrative and likeable characters. The author demonstrates strong writing skills and creates an engaging world. I would gladly read more from this author.',
        ],
        [
            'rating' => 4,
            'comment' => 'This is a well-written and entertaining book. The characters feel real and the plot is engaging. Not perfect, but definitely worth reading.',
        ],
        
        // 3-STAR REVIEWS - NEUTRAL / MIXED
        [
            'rating' => 3,
            'comment' => 'It was an okay read. The story has its moments and some parts are quite interesting, but it did not completely captivate me. The pacing was uneven at times and some characters felt underdeveloped. Not bad, but not exceptional either.',
        ],
        [
            'rating' => 3,
            'comment' => 'Mixed feelings about this one. The first half was excellent, but the second half lost momentum and felt rushed. The concept was interesting but the execution could have been better. Average overall.',
        ],
        [
            'rating' => 3,
            'comment' => 'Decent book with some good ideas but also some significant flaws. The writing quality is generally good though the dialogue occasionally felt forced. It is readable but lacks the spark to make it truly memorable.',
        ],
        [
            'rating' => 3,
            'comment' => 'Neither particularly good nor bad. The book serves its purpose as light entertainment but does not stand out. Some elements were confusing and the ending felt unsatisfying. It is the type of book you read once and forget about.',
        ],
        
        // 2-STAR REVIEWS - NEGATIVE
        [
            'rating' => 2,
            'comment' => 'Disappointing. I had high expectations based on reviews but this book did not deliver. The plot feels contrived, the characters are not compelling, and there are numerous grammatical errors. It felt like a chore to read through to the end.',
        ],
        [
            'rating' => 2,
            'comment' => 'Not my cup of tea at all. The pacing drags significantly and I could not connect with any of the characters. The world-building felt lazy and the dialogue was awkward. I would not recommend this to others.',
        ],
        [
            'rating' => 2,
            'comment' => 'Below average. While the author shows some potential, this particular book has too many issues to overlook. Plot holes, inconsistent characterization, and poor editing detract from any positive elements.',
        ],
        [
            'rating' => 2,
            'comment' => 'I struggled to finish this book. The narrative felt disjointed and the main character was difficult to care about. Too many unnecessary subplots that did not contribute to the story.',
        ],
        
        // 1-STAR REVIEWS - EXTREMELY NEGATIVE
        [
            'rating' => 1,
            'comment' => 'Absolutely terrible. Possibly the worst book I have ever read. The writing is poor, the plot is incoherent, and the characters are flat and unlikeable. Complete waste of time and money. I could not even finish it.',
        ],
        [
            'rating' => 1,
            'comment' => 'Dreadful! This book has no redeeming qualities. The author clearly did not put any effort into writing or editing. Numerous plot holes, terrible dialogue, and a confusing narrative. Avoid at all costs.',
        ],
        [
            'rating' => 1,
            'comment' => 'This is an embarrassment to the publishing industry. Awful writing, nonsensical plot, and utterly pointless characters. I am shocked this was even published. Do not waste your money.',
        ],
        [
            'rating' => 1,
            'comment' => 'Horrible from start to finish. I forced myself through the first fifty pages hoping it would improve but it only got worse. The concept had potential but the execution is abysmal.',
        ],

        // ADDITIONAL DIVERSE REVIEWS FOR RICHER ANALYSIS
        [
            'rating' => 5,
            'comment' => 'This book restored my faith in literature! The author has created something truly special that resonates on multiple levels. The narrative flows beautifully and the themes are profound and meaningful. Absolutely stunning work!',
        ],
        [
            'rating' => 4,
            'comment' => 'Enjoyable and well-crafted. The characters are relatable and the plot moves at a good pace. There are some minor issues with continuity, but they do not significantly impact the overall reading experience.',
        ],
        [
            'rating' => 3,
            'comment' => 'Somewhat predictable but still entertaining. I found myself guessing the plot twists early on, but the journey was still pleasant enough. Not groundbreaking but serviceable.',
        ],
        [
            'rating' => 2,
            'comment' => 'Started strong but deteriorated quickly. The initial premise was promising but the development was poor. Unsatisfying conclusion that left me frustrated.',
        ],
        [
            'rating' => 1,
            'comment' => 'A complete disappointment. This is not even worth giving star rating to. Total failure on every level. Unreadable and unfinishable.',
        ],
    ];

    public function run(): void
    {
        $this->command->info('Starting AI-focused review seeding...');

        // Get sample of books (avoid querying all 1M)
        $sampleBooks = Book::inRandomOrder()->limit(10)->get();
        
        if ($sampleBooks->isEmpty()) {
            $this->command->warn('No books found. Please seed books first.');
            return;
        }

        // Get customers (exclude admin)
        $customers = User::where('role', 'customer')->limit(15)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please seed users first.');
            return;
        }

        $createdCount = 0;

        foreach ($sampleBooks as $book) {
            // Create 3-5 reviews per book using different customers
            $reviewCount = rand(3, 5);
            $selectedCustomers = $customers->random(min($reviewCount, $customers->count()));

            foreach ($selectedCustomers as $customer) {
                // Skip if user already reviewed this book
                $existingReview = Review::where('user_id', $customer->id)
                    ->where('book_id', $book->id)
                    ->exists();

                if ($existingReview) {
                    continue;
                }

                $reviewData = $this->reviews[array_rand($this->reviews)];

                Review::create([
                    'user_id' => $customer->id,
                    'book_id' => $book->id,
                    'rating' => $reviewData['rating'],
                    'comment' => $reviewData['comment'],
                    'is_flagged_by_ai' => false,
                    'created_at' => now()->subDays(rand(1, 60)),
                    'updated_at' => now(),
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Successfully created {$createdCount} diverse reviews!");
        $this->command->info('Reviews are ready for AI sentiment analysis, summarization, and content moderation demonstration.');
        $this->command->line('');
        $this->command->line('Review Distribution:');
        $this->command->line('  - 5 Star (5+ reviews):     Excellent AI positive sentiment detection');
        $this->command->line('  - 4 Star (4-5 reviews):    Good positive sentiment with minor concerns');
        $this->command->line('  - 3 Star (3-4 reviews):    Neutral sentiment, mixed opinions');
        $this->command->line('  - 2 Star (2-3 reviews):    Negative sentiment with specific complaints');
        $this->command->line('  - 1 Star (1-2 reviews):    Extremely negative sentiment, toxicity detection');
    }
}

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please seed users first.');
            return;
        }

        $createdCount = 0;

        foreach ($sampleBooks as $book) {
            // Create 2-3 reviews per book using different customers
            $reviewCount = rand(2, 3);
            $selectedCustomers = $customers->random(min($reviewCount, $customers->count()));

            foreach ($selectedCustomers as $customer) {
                // Skip if user already reviewed this book
                $existingReview = Review::where('user_id', $customer->id)
                    ->where('book_id', $book->id)
                    ->exists();

                if ($existingReview) {
                    continue;
                }

                $reviewData = $this->reviews[array_rand($this->reviews)];

                Review::create([
                    'user_id' => $customer->id,
                    'book_id' => $book->id,
                    'rating' => $reviewData['rating'],
                    'comment' => $reviewData['comment'],
                    'is_flagged_by_ai' => false,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Successfully created {$createdCount} reviews for AI analysis!");
        $this->command->info('AI will now analyze these reviews and generate sentiment insights.');
    }
}
