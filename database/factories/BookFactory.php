<?php

namespace Database\Factories;
use App\Models\Category;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {  
        $titles = [
            'The Silent Observer', 'Midnight in the Garden', 'Beyond the Horizon',
            'The Last Chapter', 'Whispers of Time', 'The Golden Thread',
            'A World Apart', 'The Hidden Path', 'Echoes of Tomorrow',
            'The Iron Gate', 'Under Crimson Skies', 'The Forgotten Letter',
            'Dancing with Shadows', 'The Lighthouse Keeper', 'Broken Wings',
            'The Winter Rose', 'Tides of Fortune', 'The Glass Castle',
            'Starlight and Ashes', 'The Wanderer Returns', 'Lost in Translation',
            'The Secret Garden Club', 'Rivers of Gold', 'The Final Curtain',
            'Between Two Worlds', 'The Paper Trail', 'Shattered Dreams',
            'The Morning Star', 'Voices in the Wind', 'The Clockmaker',
        ];

        $descriptions = [
            'A captivating story that takes readers on an unforgettable journey through love, loss, and redemption. This beautifully written novel explores the depths of human emotion and the power of second chances.',
            'An enthralling tale of adventure and discovery that will keep you turning pages long into the night. Set against a backdrop of stunning landscapes and rich characters.',
            'A thought-provoking exploration of what it means to be human in a rapidly changing world. This book challenges readers to see beyond the ordinary and embrace the extraordinary.',
            'A masterfully crafted narrative that weaves together multiple timelines and perspectives into a rich tapestry of storytelling. Every chapter reveals new layers of meaning.',
            'A heartwarming story about family, friendship, and the bonds that hold us together through the most challenging times. Perfect for readers who love emotional depth.',
            'A gripping thriller that keeps you guessing until the very last page. Full of twists, turns, and unexpected revelations that will leave you breathless.',
        ];

        $coverFiles = collect(File::files(storage_path('app/public/covers')))
            ->map(fn ($file) => 'covers/' . $file->getFilename())
            ->values()
            ->toArray();

        return [
            'category_id' => Category::factory(),
            'title' => fake()->unique()->randomElement($titles),
            'author' => fake('en_US')->name(),
            'isbn' => fake()->unique()->isbn13(),
            'price' => fake()->randomFloat(2, 9.99, 49.99),
            'stock_quantity' => fake()->numberBetween(5, 50),
            'description' => fake()->randomElement($descriptions),
            'cover_image' => !empty($coverFiles) ? fake()->randomElement($coverFiles) : null,
        ];
    }
}
