<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookApiController extends Controller
{
    private const FIELD_MAP = [
        'id' => 'id',
        'title' => 'title',
        'author' => 'author',
        'isbn' => 'isbn',
        'price' => 'price',
        'stockQuantity' => 'stock_quantity',
        'stock_quantity' => 'stock_quantity',
        'categoryId' => 'category_id',
        'category_id' => 'category_id',
        'categoryName' => 'category_name',
        'category_name' => 'category_name',
        'createdAt' => 'created_at',
        'created_at' => 'created_at',
        'updatedAt' => 'updated_at',
        'updated_at' => 'updated_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fields' => 'nullable|string',
            'perPage' => 'nullable|integer|min:1|max:100',
            'cursor' => 'nullable|string',
            'search' => 'nullable|string|max:255',
            'categoryId' => 'nullable|integer|exists:categories,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $perPage = (int) ($validated['perPage'] ?? 15);
        $selectedFields = $this->resolveRequestedFields($validated['fields'] ?? null);

        $query = Book::query()->with('category')->orderBy('id');

        $categoryId = $validated['categoryId'] ?? $validated['category_id'] ?? null;
        if ($categoryId) {
            $query->where('category_id', (int) $categoryId);
        }

        if (! empty($validated['search'])) {
            $search = trim((string) $validated['search']);
            $query->where(function ($nested) use ($search) {
                $nested->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        $books = $query->cursorPaginate($perPage, ['*'], 'cursor', $validated['cursor'] ?? null);

        $items = $books->getCollection()
            ->map(fn (Book $book) => $this->transformBook($book, $selectedFields))
            ->values()
            ->all();

        $payload = [
            'data' => $items,
            'meta' => [
                'perPage' => $books->perPage(),
                'nextCursor' => optional($books->nextCursor())->encode(),
                'prevCursor' => optional($books->previousCursor())->encode(),
                'hasMorePages' => $books->hasMorePages(),
                'requestedFields' => $selectedFields,
            ],
        ];

        return $this->etaggedJson($request, $payload);
    }

    public function show(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'fields' => 'nullable|string',
        ]);

        $book->load('category');
        $selectedFields = $this->resolveRequestedFields($validated['fields'] ?? null);

        $payload = [
            'data' => $this->transformBook($book, $selectedFields),
        ];

        return $this->etaggedJson($request, $payload);
    }

    private function transformBook(Book $book, array $selectedFields): array
    {
        $normalized = [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'price' => $book->price,
            'stockQuantity' => $book->stock_quantity,
            'categoryId' => $book->category_id,
            'categoryName' => $book->category->name ?? null,
            'createdAt' => $book->created_at?->toIso8601String(),
            'updatedAt' => $book->updated_at?->toIso8601String(),
        ];

        return array_intersect_key($normalized, array_flip($selectedFields));
    }

    private function resolveRequestedFields(?string $fields): array
    {
        $default = ['id', 'title', 'author', 'isbn', 'price', 'stockQuantity', 'categoryId', 'categoryName', 'createdAt', 'updatedAt'];

        if (! $fields) {
            return $default;
        }

        $requested = array_filter(array_map('trim', explode(',', $fields)));
        if ($requested === []) {
            return $default;
        }

        $normalized = [];
        foreach ($requested as $field) {
            $mapped = self::FIELD_MAP[$field] ?? null;
            if (! $mapped) {
                continue;
            }

            $camel = match ($mapped) {
                'stock_quantity' => 'stockQuantity',
                'category_id' => 'categoryId',
                'category_name' => 'categoryName',
                'created_at' => 'createdAt',
                'updated_at' => 'updatedAt',
                default => $mapped,
            };

            $normalized[] = $camel;
        }

        $normalized = array_values(array_unique($normalized));

        return $normalized !== [] ? $normalized : $default;
    }

    private function etaggedJson(Request $request, array $payload): JsonResponse
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $etag = '"'.sha1((string) $encoded).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304, [
                'ETag' => $etag,
                'Cache-Control' => 'private, must-revalidate',
            ]);
        }

        return response()->json($payload, 200, [
            'ETag' => $etag,
            'Cache-Control' => 'private, must-revalidate',
        ]);
    }
}
