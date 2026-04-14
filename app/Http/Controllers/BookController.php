<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with('category');

        // Filter by category if provided and not empty
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Search by title or author case-insensitive should be non-empty
        if ($request->filled('search')) {
            $search = mb_strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(author) LIKE ?', ["%{$search}%"]);
            });
        }

        // Filter by price range and only apply when numeric
        if ($request->filled('min_price') && is_numeric($request->min_price)) {
            $min = floatval($request->min_price);
            $query->where('price', '>=', $min);
        }

        if ($request->filled('max_price') && is_numeric($request->max_price)) {
            $max = floatval($request->max_price);
            $query->where('price', '<=', $max);
        }

        // Sort options
        $sort = $request->get('sort', 'date');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                // Compute average rating from reviews and order by it.
                $reviewsSub = DB::table('reviews')
                    ->selectRaw('book_id, AVG(rating) as avg_rating')
                    ->groupBy('book_id');

                $query->leftJoinSub($reviewsSub, 'r', function ($join) {
                    $join->on('books.id', '=', 'r.book_id');
                })
                ->select('books.*')
                ->orderByRaw('COALESCE(r.avg_rating, 0) desc')
                ->orderBy('books.id', 'asc');

                break;
            default:
                // Use created_at desc but add a stable tiebreaker to avoid non-deterministic ordering
                $query->orderBy('created_at', 'desc')->orderBy('id', 'asc');
        }

        $books = $query->paginate(12);
        $categories = Category::all();

        return view('books.index', compact('books', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Book::class);

        $categories = Category::all();
        return view('books.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('cover_image')) {
            // Resize image to max 400x600 and save using GD (no Intervention required)
            $image = $request->file('cover_image');

            if (method_exists($image, 'isValid') && !$image->isValid()) {
                return back()->withErrors(['cover_image' => 'Upload failed (PHP reported an error).']);
            }

            $imagePath = is_string($image) ? $image : $image->getRealPath();
            if (!$imagePath || !file_exists($imagePath)) {
                return back()->withErrors(['cover_image' => 'Uploaded file not found on disk.']);
            }

            $destDir = storage_path('app/public/covers');
            if (!file_exists($destDir)) {
                @mkdir($destDir, 0755, true);
            }

            $filename = uniqid() . '.' . $image->getClientOriginalExtension();
            $destPath = $destDir . '/' . $filename;

            $err = null;
            $ok = $this->resizeAndSaveWithGd($imagePath, $destPath, 400, 600, 85, $err);
            if (!$ok) {
                return back()->withErrors(['cover_image' => $err ?? 'Failed to process image (GD not available or file invalid).']);
            }

            $validated['cover_image'] = 'covers/' . $filename;
        }

        Book::create($validated);

        return redirect()->route('books.index')
            ->with('success', 'Book added successfully!');
    }

    public function show(Book $book)
    {
        $book->load(['category', 'reviews.user']);
        
        // Check if user has a COMPLETED order with this book (can review)
        $hasPurchased = false;
        if (auth()->check()) {
            $hasPurchased = auth()->user()->orders()
                ->where('status', 'completed')
                ->whereHas('orderItems', function ($query) use ($book) {
                    $query->where('book_id', $book->id);
                })->exists();
        }
        
        return view('books.show', compact('book', 'hasPurchased'));
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $categories = Category::all();
        return view('books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books,isbn,' . $book->id,
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            // Resize image to max 400x600 and save using GD (no Intervention required)
            $image = $request->file('cover_image');

            if (method_exists($image, 'isValid') && !$image->isValid()) {
                return back()->withErrors(['cover_image' => 'Upload failed (PHP reported an error).']);
            }

            $imagePath = is_string($image) ? $image : $image->getRealPath();
            if (!$imagePath || !file_exists($imagePath)) {
                return back()->withErrors(['cover_image' => 'Uploaded file not found on disk.']);
            }

            $destDir = storage_path('app/public/covers');
            if (!file_exists($destDir)) {
                @mkdir($destDir, 0755, true);
            }

            $filename = uniqid() . '.' . $image->getClientOriginalExtension();
            $destPath = $destDir . '/' . $filename;

            $err = null;
            $ok = $this->resizeAndSaveWithGd($imagePath, $destPath, 400, 600, 85, $err);
            if (!$ok) {
                return back()->withErrors(['cover_image' => $err ?? 'Failed to process image (GD not available or file invalid).']);
            }

            $validated['cover_image'] = 'covers/' . $filename;
        }

        $book->update($validated);

        return redirect()->route('books.show', $book)
            ->with('success', 'Book updated successfully!');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Book deleted successfully!');
    }


    protected function resizeAndSaveWithGd(string $srcPath, string $destPath, int $maxW, int $maxH, int $quality = 85, ?string & $error = null): bool
    {
        $error = null;
        if (!extension_loaded('gd')) {
            $error = 'GD extension is not available on this server.';
            return false;
        }

        $info = @getimagesize($srcPath);
        if ($info === false) {
            $error = 'Unable to read image information.';
            return false;
        }

        [$w, $h, $type] = [$info[0], $info[1], $info[2]];
        if ($w <= 0 || $h <= 0) {
            $error = 'Invalid image dimensions.';
            return false;
        }

        // Reject extremely large images to avoid OOM. Conservative cap on total pixels.
        $maxPixels = 12000000; // ~12 million pixels (e.g., 4000x3000)
        if (($w * $h) > $maxPixels) {
            $error = 'Image dimensions are too large. Please resize the image before uploading.';
            return false;
        }

        // Estimate memory required to load the image (rough estimate: 4 bytes per pixel).
        $estimatedBytes = (float) $w * (float) $h * 4.0;
        $currentUsage = memory_get_usage();

        // Helper to parse shorthand memory values like '128M'
        $parseBytes = function ($val) {
            if (is_int($val)) return $val;
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $num = (int) $val;
            switch ($last) {
                case 'g': return $num * 1024 * 1024 * 1024;
                case 'm': return $num * 1024 * 1024;
                case 'k': return $num * 1024;
                default: return $num;
            }
        };

        $memLimit = @ini_get('memory_limit');
        $memLimitBytes = $memLimit ? $parseBytes($memLimit) : 0;

        $available = $memLimitBytes > 0 ? ($memLimitBytes - $currentUsage) : PHP_INT_MAX;

        // If estimated needs exceed available memory, try increasing memory_limit up to 512MB.
        if ($memLimitBytes > 0 && $estimatedBytes > ($available * 0.9)) {
            $needed = (int) ($currentUsage + $estimatedBytes + (20 * 1024 * 1024)); // add 20MB headroom
            $cap = 512 * 1024 * 1024; // 512MB cap
            $newLimit = min(max($memLimitBytes, $needed), $cap);
            // Only attempt to increase if it would actually be larger than current
            if ($newLimit > $memLimitBytes) {
                @ini_set('memory_limit', (string) $newLimit);
                // Recompute available
                $memLimitBytes = @ini_get('memory_limit') ? $parseBytes(@ini_get('memory_limit')) : $memLimitBytes;
                $available = $memLimitBytes > 0 ? ($memLimitBytes - $currentUsage) : PHP_INT_MAX;
            }
        }

        // If still insufficient, refuse to process to avoid fatal OOM.
        if ($memLimitBytes > 0 && $estimatedBytes > ($available * 0.95)) {
            $error = 'Not enough memory to process this image. Reduce image size or increase PHP memory_limit.';
            return false;
        }

        $ratio = min($maxW / $w, $maxH / $h, 1);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);
        if ($dst === false) {
            return false;
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($srcPath);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($srcPath);
                // preserve transparency
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($srcPath);
                break;
            case IMAGETYPE_WEBP:
                if (!function_exists('imagecreatefromwebp')) {
                    return false;
                }
                $src = @imagecreatefromwebp($srcPath);
                break;
            default:
                return false;
        }

        if ($src === false) {
            $error = 'Failed to read the source image (possibly corrupt or unsupported).';
            return false;
        }

        if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h)) {
            imagedestroy($src);
            imagedestroy($dst);
            $error = 'Failed while resampling the image.';
            return false;
        }

        $ok = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $ok = imagejpeg($dst, $destPath, $quality);
                break;
            case IMAGETYPE_PNG:
                // PNG quality is 0 (no compression) to 9
                $pngQuality = 6;
                $ok = imagepng($dst, $destPath, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $ok = imagegif($dst, $destPath);
                break;
            case IMAGETYPE_WEBP:
                $ok = imagewebp($dst, $destPath, $quality);
                break;
        }

        imagedestroy($src);
        imagedestroy($dst);
        if (! $ok) {
            $error = 'Failed to save the processed image.';
        }
        return (bool) $ok;
    }
}
