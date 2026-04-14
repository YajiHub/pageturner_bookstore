<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('A verification code has been sent to your email address. Please enter the code below to complete your login.') }}
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <!-- OTP Code -->
        <div>
            <x-input-label for="code" :value="__('Verification Code')" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-2xl tracking-widest" type="text" name="code" required autofocus autocomplete="one-time-code" maxlength="10" placeholder="000000" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-4">
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
                {{ __('Resend Code') }}
            </button>
        </form>
    </div>

    <div class="mt-6 border-t pt-4">
        <p class="text-sm text-gray-500 mb-2">{{ __('Lost access to your email? Use a recovery code instead.') }}</p>
        <form method="POST" action="{{ route('two-factor.verify') }}">
            @csrf
            <div>
                <x-input-label for="recovery_code" :value="__('Recovery Code')" />
                <x-text-input id="recovery_code" class="block mt-1 w-full" type="text" name="code" placeholder="Enter recovery code" />
            </div>
            <div class="mt-2">
                <x-primary-button>
                    {{ __('Use Recovery Code') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
