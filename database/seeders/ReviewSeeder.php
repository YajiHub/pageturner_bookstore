<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Sample reviews designed to showcase Lab 8 AI functionality
     * Includes positive, negative, neutral, and mixed sentiment for AI testing
     */
    private array $reviews = [
        [
            'rating' => 5,
            'comment' => 'Absolutely outstanding book! I couldn\'t put it down. The author\'s writing style is captivating and the storyline keeps you engaged from start to finish. Highly recommend this to anyone who enjoys quality literature. This is a masterpiece!',
        ],
        [
            'rating' => 5,
            'comment' => 'Brilliant work! The characters are well-developed and relatable. The plot twists were unexpected and kept me on the edge of my seat. Definitely one of the best books I\'ve read this year. Simply amazing!',
        ],
        [
            'rating' => 4,
            'comment' => 'Very good book. The story is compelling and the writing is clear. Some parts could have been developed further, but overall it\'s a solid read. I enjoyed it and would recommend it to others.',
        ],
        [
            'rating' => 4,
            'comment' => 'Great read! The author has done an excellent job of creating an immersive world. Characters feel authentic and the pacing is perfect. Felt a bit rushed in the final chapters, but still very entertaining.',
        ],
        [
            'rating' => 3,
            'comment' => 'It was okay. The book has its moments but felt somewhat predictable. Some parts were interesting while others dragged. Not bad, but not exceptional either. Average read overall.',
        ],
        [
            'rating' => 3,
            'comment' => 'Decent book. The premise is interesting but execution could have been better. Writing quality is good, though the pacing felt uneven at times. Worth reading but not life-changing.',
        ],
        [
            'rating' => 2,
            'comment' => 'Disappointing. I had high expectations but the book didn\'t deliver. The plot felt contrived and character development was weak. Too many grammatical errors. Not recommended.',
        ],
        [
            'rating' => 2,
            'comment' => 'Not my cup of tea. The story felt dragging and I couldn\'t connect with any of the characters. The ending was unsatisfying. There are better books out there.',
        ],
        [
            'rating' => 1,
            'comment' => 'Terrible. Couldn\'t even finish it. Poor writing, boring plot, and flat characters. Complete waste of time and money. Worst book I\'ve read recently. Avoid at all costs.',
        ],
        [
            'rating' => 1,
            'comment' => 'Awful. The author clearly didn\'t put enough effort into this. Plot holes everywhere, inconsistent characterization, and the dialogue is cringe. Extremely disappointed.',
        ],
        [
            'rating' => 5,
            'comment' => 'This is phenomenal! The author has created something truly special. The world-building is exceptional, the characters feel real, and the emotional depth is remarkable. A must-read for everyone!',
        ],
        [
            'rating' => 3,
            'comment' => 'Mixed feelings. The first half was excellent, but the second half lost momentum. Some great ideas but uneven execution. Has potential but needs refinement.',
        ],
    ];

    public function run(): void
    {
        $this->command->info('Starting review seeding for AI demonstration...');

        // Get sample of books (avoid querying all 1M)
        $sampleBooks = Book::inRandomOrder()->limit(5)->get();
        
        if ($sampleBooks->isEmpty()) {
            $this->command->warn('No books found. Please seed books first.');
            return;
        }

        // Get customers (exclude admin)
        $customers = User::where('role', 'customer')->limit(10)->get();

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
