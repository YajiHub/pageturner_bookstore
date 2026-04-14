<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Book;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            'Absolutely loved this book! Could not put it down.',
            'A great read from start to finish. Highly recommended.',
            'The characters were well developed and the plot kept me engaged throughout.',
            'One of the best books I have read this year. Truly outstanding.',
            'Interesting premise but the ending felt a bit rushed.',
            'Beautifully written with vivid descriptions that bring the story to life.',
            'A solid read. Not the best I have ever read, but enjoyable nonetheless.',
            'This book exceeded all my expectations. A must-read for everyone.',
            'The author has a wonderful way with words. Every page was a pleasure.',
            'Good story but some parts dragged on a bit too long for my taste.',
            'An emotional rollercoaster that had me laughing and crying in equal measure.',
            'Well researched and thoughtfully written. You can tell the author cares about the subject.',
            'I found myself thinking about this book long after I finished it.',
            'Perfect for a weekend read. Light, fun, and entertaining.',
            'The plot twists caught me completely off guard. Brilliant storytelling.',
        ];

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->randomElement($comments),
        ];
    }
}
