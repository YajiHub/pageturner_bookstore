<div class="fixed top-20 right-4 z-[80] flex w-[22rem] max-w-[90vw] flex-col gap-3 pointer-events-none">
    @if(session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3400)" x-show="show" x-transition class="pointer-events-auto rounded-lg border border-emerald-200 bg-white shadow-lg">
            <div class="flex items-start gap-3 p-4">
                <div class="mt-0.5 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-emerald-700">Success</p>
                    <p class="mt-0.5 text-sm text-gray-700">{{ session('success') }}</p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4200)" x-show="show" x-transition class="pointer-events-auto rounded-lg border border-red-200 bg-white shadow-lg">
            <div class="flex items-start gap-3 p-4">
                <div class="mt-0.5 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"></path></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-red-700">Error</p>
                    <p class="mt-0.5 text-sm text-gray-700">{{ session('error') }}</p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5200)" x-show="show" x-transition class="pointer-events-auto rounded-lg border border-red-200 bg-white shadow-lg">
            <div class="flex items-start gap-3 p-4">
                <div class="mt-0.5 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"></path></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-red-700">Please review your input</p>
                    <ul class="mt-1 text-sm text-gray-700 list-disc pl-5 space-y-0.5 max-h-28 overflow-y-auto">
                        @foreach(array_slice($errors->all(), 0, 4) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600" aria-label="Close">&times;</button>
            </div>
        </div>
    @endif
</div>
