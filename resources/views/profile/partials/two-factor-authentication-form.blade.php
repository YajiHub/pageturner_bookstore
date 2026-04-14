<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Two-Factor Authentication') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Add an extra layer of security to your account by enabling two-factor authentication. When enabled, you will be required to enter a code sent to your email during login.') }}
        </p>
    </header>

    @if (session('status') === '2fa-enabled')
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm font-medium text-green-800">
                {{ __('Two-factor authentication has been enabled!') }}
            </p>

            @if (session('recoveryCodes'))
                <div class="mt-3">
                    <p class="text-sm text-green-700 font-semibold">
                        {{ __('Save these recovery codes in a safe place. They can be used to access your account if you lose access to your email:') }}
                    </p>
                    <div class="mt-2 bg-white p-3 rounded border font-mono text-sm">
                        @foreach (session('recoveryCodes') as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if (session('status') === '2fa-disabled')
        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm font-medium text-yellow-800">
                {{ __('Two-factor authentication has been disabled.') }}
            </p>
        </div>
    @endif

    <div class="mt-4">
        @if (auth()->user()->two_factor_enabled)
            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    {{ __('Enabled') }}
                </span>
                <span class="text-sm text-gray-600">{{ __('2FA is currently active on your account.') }}</span>
            </div>

            <form method="POST" action="{{ route('two-factor.disable') }}">
                @csrf
                <x-danger-button>
                    {{ __('Disable Two-Factor Authentication') }}
                </x-danger-button>
            </form>
        @else
            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ __('Disabled') }}
                </span>
                <span class="text-sm text-gray-600">{{ __('2FA is not enabled on your account.') }}</span>
            </div>

            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <x-primary-button>
                    {{ __('Enable Two-Factor Authentication') }}
                </x-primary-button>
            </form>
        @endif
    </div>
</section>
