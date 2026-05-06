<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $book->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $book->title }}</h1>
                            <p class="text-lg text-gray-600 mb-4">By {{ $book->author }}</p>
                            
                            <div class="mb-4 space-y-1 text-sm text-gray-500">
                                <p><strong>ISBN:</strong> {{ $book->isbn }}</p>
                                <p><strong>Publisher:</strong> {{ $book->publisher }}</p>
                                <p><strong>Format:</strong> {{ $book->format }}</p>
                                <p><strong>Published:</strong> {{ \Carbon\Carbon::parse($book->published_at)->format('F Y') }}</p>
                            </div>

                            <p class="text-2xl font-bold text-indigo-600 mb-4">₱{{ number_format($book->price, 2) }}</p>
                            
                            <div class="prose max-w-none text-gray-700">
                                <p>{{ $book->description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                try {
                    $aiInsight = \Illuminate\Support\Facades\DB::table('ai_book_insights')->where('book_id', $book->id)->first();
                } catch (\Exception $e) {
                    $aiInsight = null;
                }
            @endphp

            @if($aiInsight && isset($aiInsight->ai_summary))
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6 mb-8 border border-indigo-100 shadow-sm">
                <div class="flex items-center mb-3">
                    <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <h3 class="text-lg font-bold text-gray-900">AI Review Consensus</h3>
                    
                    @php
                        $sentiment = $aiInsight->overall_sentiment ?? 'Neutral';
                        $sentimentColor = 'bg-blue-100 text-blue-800';
                        if ($sentiment === 'Positive') $sentimentColor = 'bg-green-100 text-green-800';
                        if ($sentiment === 'Negative') $sentimentColor = 'bg-red-100 text-red-800';
                        if ($sentiment === 'Neutral') $sentimentColor = 'bg-gray-100 text-gray-800';
                    @endphp
                    
                    <span class="ml-auto text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $sentimentColor }}">
                        {{ $sentiment }} Sentiment
                    </span>
                </div>
                <p class="text-gray-700 italic">"{{ $aiInsight->ai_summary }}"</p>
                <p class="text-xs text-gray-400 mt-3 flex justify-between">
                    <span>✨ Synthesized by AI from {{ $aiInsight->reviews_analyzed_count ?? 0 }} reader reviews.</span>
                    <span>Last updated: {{ $aiInsight->updated_at ? \Carbon\Carbon::parse($aiInsight->updated_at)->diffForHumans() : 'Just now' }}</span>
                </p>
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Customer Reviews</h2>

                    @auth
                        <form action="{{ route('reviews.store', $book) }}" method="POST" class="mb-8 p-5 bg-gray-50 border border-gray-100 rounded-lg">
                            @csrf
                            <h3 class="font-semibold text-gray-800 mb-4">Write a Review</h3>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Rating (1-5)</label>
                                <select name="rating" class="border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm w-full sm:w-auto">
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Average</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Terrible</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Your Review</label>
                                <textarea name="comment" rows="3" class="border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm w-full" placeholder="What did you think of this book?"></textarea>
                            </div>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded transition duration-150">
                                Submit Review
                            </button>
                        </form>
                    @else
                        <div class="mb-8 p-4 bg-gray-50 border border-gray-100 rounded-lg text-gray-600">
                            Please <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">log in</a> to write a review.
                        </div>
                    @endauth

                    <div class="space-y-6">
                        @forelse($book->reviews as $review)
                            
                            @if(!$review->is_flagged_by_ai)
                                <div class="border-b border-gray-100 pb-5">
                                    <div class="flex items-center mb-1">
                                        <span class="font-bold text-gray-900 mr-2">{{ $review->user->name ?? 'Anonymous' }}</span>
                                        <span class="text-yellow-400 text-sm tracking-widest">
                                            {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                                        </span>
                                    </div>
                                    <p class="text-gray-500 text-xs mb-3">{{ $review->created_at->format('M d, Y') }}</p>
                                    <p class="text-gray-800">{{ $review->comment }}</p>
                                    
                                    @if(auth()->check() && (auth()->id() === $review->user_id || auth()->user()->role === 'admin'))
                                        <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="mt-3">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 text-xs hover:text-red-700 hover:underline" onclick="return confirm('Delete this review?')">Delete Review</button>
                                        </form>
                                    @endif
                                </div>
                            
                            @else
                                @if(auth()->check() && auth()->user()->role === 'admin')
                                    <div class="bg-red-50 p-4 border border-red-200 rounded-lg mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-red-700 font-bold text-sm flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                HIDDEN BY AI: {{ $review->ai_moderation_reason }}
                                            </span>
                                        </div>
                                        <p class="text-gray-800 italic line-through opacity-75">{{ $review->comment }}</p>
                                        <p class="text-xs text-gray-500 mt-2 font-medium">Attempted post by: {{ $review->user->name ?? 'Anonymous' }}</p>
                                        <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="mt-3">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-700 text-xs font-bold hover:underline" onclick="return confirm('Permanently delete this toxic review?')">Delete Permanently</button>
                                        </form>
                                    </div>
                                @endif
                            @endif

                        @empty
                            <div class="text-center py-8">
                                <p class="text-gray-500 italic">No reviews yet. Be the first to share your thoughts!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>