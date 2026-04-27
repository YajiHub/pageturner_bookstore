<nav id="site-navbar" class="js-main-nav sticky top-0 z-50 bg-gray-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">
                            PageTurner
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold">
                            PageTurner
                        </a>
                    @endif
                @endauth
                @guest
                    <a href="{{ route('home') }}" class="text-xl font-bold">
                        PageTurner
                    </a>
                @endguest

                <div class="hidden md:flex ml-10 space-x-4 items-center z-50">
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="hover:bg-gray-700 px-3 py-2 rounded-md inline-flex items-center text-sm font-medium transition-colors">
                            Storefront
                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="open" x-transition class="absolute left-0 mt-2 w-48 rounded-md bg-white shadow-lg border border-gray-100" style="display:none;">
                            <div class="py-1">
                                <a href="{{ route('home') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Home</a>
                                <a href="{{ route('books.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Books</a>
                                <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Categories</a>
                            </div>
                        </div>
                    </div>

                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-gray-900' : '' }}">
                                Dashboard
                            </a>
                            
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open" class="hover:bg-gray-700 px-3 py-2 rounded-md inline-flex items-center text-sm font-medium transition-colors">
                                    Manage Catalog
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="open" x-transition class="absolute left-0 mt-2 w-48 rounded-md bg-white shadow-lg border border-gray-100" style="display:none;">
                                    <div class="py-1">
                                        <a href="{{ route('admin.books.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add Book</a>
                                        <a href="{{ route('admin.categories.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add Category</a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <a href="{{ route('admin.backups.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">System Backups</a>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('dashboard') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-gray-900' : '' }}">
                                My Account
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="flex items-center space-x-4">
                @guest
                    <a href="{{ route('login') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-4 py-2 rounded-md font-medium">
                        Register
                    </a>
                @endguest

                @auth
                    @php
                        $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
                        $latestNotifications = auth()->user()->notifications()->latest()->take(6)->get();
                    @endphp

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative hover:bg-gray-700 px-3 py-2 rounded-md" aria-label="Notifications">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            @if($unreadNotificationsCount > 0)
                                <span class="absolute -top-1 -right-1 inline-flex min-w-5 h-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>
                            @endif
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-80 rounded-md bg-white text-gray-800 shadow-lg border border-gray-100 z-50" style="display:none;">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <span class="text-sm font-semibold">Notifications</span>
                                <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View all</a>
                            </div>

                            @if($latestNotifications->isEmpty())
                                <div class="px-4 py-6 text-sm text-gray-500">No notifications yet.</div>
                            @else
                                <div class="max-h-80 overflow-y-auto">
                                    @foreach($latestNotifications as $notification)
                                        <div class="px-4 py-3 border-b border-gray-100 {{ $notification->read_at ? '' : 'bg-indigo-50' }}">
                                            <p class="text-sm">{{ \App\Support\NotificationText::resolve($notification->type, (array) $notification->data) }}</p>
                                            <div class="mt-2 flex items-center justify-between">
                                                <span class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                                                @if(! $notification->read_at)
                                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                        @csrf
                                                        <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Mark read</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($unreadNotificationsCount > 0)
                                    <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="px-4 py-3 border-t border-gray-100">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Mark all as read</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('orders.index') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md">
                            Customer Orders
                        </a>
                    @else
                        <a href="{{ route('orders.index') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md">
                            My Orders
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md text-indigo-200 hover:text-white">
                        {{ auth()->user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:bg-gray-700 px-3 py-2 rounded-md">
                            Logout
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</nav>