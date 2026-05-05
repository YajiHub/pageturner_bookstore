<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    // Cache Category IDs to avoid 1 Million DB Lookups
    protected static array $categoryIds = [];
    protected static array $publishers = [
        'Penguin Random House', 'HarperCollins', 'Simon & Schuster', 'Hachette Livre',
        'Macmillan Publishers', 'Scholastic', 'Pearson', 'McGraw-Hill', 'Oxford University Press',
        'Wiley', 'Springer Nature', 'Cengage', 'Bloomsbury', 'Routledge', 'Elsevier'
    ];
    protected static array $formats = ['Hardcover', 'Paperback', 'E-book', 'Audiobook'];

    public function definition(): array
    {
        if (empty(self::$categoryIds)) {
            self::$categoryIds = Category::pluck('id')->toArray();
            if (empty(self::$categoryIds)) {
                self::$categoryIds = [1, 2, 3, 4, 5]; // Fallback if no categories exist
            }
        }

        $format = $this->faker->randomElement(self::$formats);
        $basePrice = match ($format) {
            'Hardcover' => $this->faker->randomFloat(2, 20, 50),
            'Paperback' => $this->faker->randomFloat(2, 10, 25),
            'E-book'    => $this->faker->randomFloat(2, 5, 15),
            'Audiobook' => $this->faker->randomFloat(2, 15, 30),
            default     => 15.99,
        };

        return [
            'isbn'           => $this->generateValidIsbn13(),
            'title'          => $this->faker->unique()->sentence(rand(2, 6)),
            'author'         => $this->faker->name(),
            'publisher'      => $this->faker->randomElement(self::$publishers),
            'description'    => $this->faker->paragraph(rand(2, 4)),
            'price'          => $basePrice,
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'category_id'    => $this->faker->randomElement(self::$categoryIds),
            'format'         => $format,
            'published_at'   => $this->faker->dateTimeBetween('-30 years', 'now')->format('Y-m-d'),
            'is_active'      => $this->faker->boolean(85), // 85% active
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
    }

    private function generateValidIsbn13(): string
    {
        $prefix = '978';
        $group = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        $publisher = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $title = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        $isbn12 = $prefix . $group . $publisher . $title;
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$isbn12[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $isbn12 . $checkDigit;
    }
}