@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
        @if(auth()->user()->unreadNotifications()->count() > 0)
            <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                @csrf
                <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Mark all as read</button>
            </form>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="bg-white rounded-lg shadow p-6 text-gray-600">
            No notifications yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
                <div class="bg-white rounded-lg shadow p-4 border {{ $notification->read_at ? 'border-gray-100' : 'border-indigo-200' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-800">{{ \App\Support\NotificationText::resolve($notification->type, (array) $notification->data) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if(! $notification->read_at)
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Mark read</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection