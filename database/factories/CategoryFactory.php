<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Fiction', 'Non-Fiction', 'Science', 'Technology', 'Biography', 'History', 'Romance', 'Mystery', 'Self-Help', 'Children'];

        return [
            'name' => fake()->unique()->randomElement($categories),
            'description' => fake('en_US')->paragraph(),
        ];
    }
}
