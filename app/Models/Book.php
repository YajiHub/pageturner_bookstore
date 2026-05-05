<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Traits\Shardable;
use Laravel\Scout\Searchable;

class Book extends Model
{
    //
    use HasFactory;

    use Searchable, Shardable;

    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'price',
        'stock_quantity',
        'description',
        'cover_image',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'author'      => $this->author,
            'publisher'   => $this->publisher,
            'description' => $this->description,
            'category'    => $this->category?->name,
            'format'      => $this->format,
        ];
    }
    public function shouldBeSearchable(): bool
    {
        return clone $this->is_active;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function readingHistories()
    {
        return $this->hasMany(ReadingHistory::class);
    }

    // Accessor for average rating
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
}
