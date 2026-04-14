@extends('layouts.app')

@section('title', 'Edit: ' . $category->name)

@section('content')
<div class="max-w-md mx-auto">
    <h1 class="text-3xl font-bold mb-6">Edit Category</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-medium mb-2">Category Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
                <textarea name="description" id="description" rows="3"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('categories.show', $category) }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition">
                    Update Category
                </button>
            </div>
        </form>

        {{-- Delete form --}}
        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="mt-4 pt-4 border-t" onsubmit="return confirm('Delete this category and all its books?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                Delete Category
            </button>
        </form>
    </div>
</div>
@endsection
