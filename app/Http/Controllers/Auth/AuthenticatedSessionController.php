<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if 2FA is enabled for this user
        if ($user->two_factor_enabled) {
            // Log the user out temporarily — they need to pass 2FA first
            Auth::logout();

            // Generate a 6-digit OTP
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store 2FA challenge data in session
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:code', $code);
            $request->session()->put('2fa:expires_at', now()->addMinutes(10));
            $request->session()->put('2fa:remember', $request->boolean('remember'));

            // Send OTP via email. If email fails (offline/SMTP), clean up and return safely.
            try {
                $user->notify(new TwoFactorCodeNotification($code));
            } catch (Throwable $e) {
                report($e);

                $request->session()->forget(['2fa:user_id', '2fa:code', '2fa:expires_at', '2fa:remember']);
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Unable to send your 2FA verification code right now. Please check your internet connection and try again.',
                ]);
            }

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
