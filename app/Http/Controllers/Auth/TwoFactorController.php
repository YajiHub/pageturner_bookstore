<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\TwoFactorCodeNotification;
use App\Notifications\TwoFactorEnabledNotification;
use App\Notifications\TwoFactorDisabledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA challenge form (enter OTP code).
     */
    public function show(Request $request)
    {
        // Only show if user passed 2FA-pending state
        if (!$request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the 2FA code submitted by the user.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = $request->session()->get('2fa:user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);
        $sessionCode = $request->session()->get('2fa:code');
        $expiresAt = $request->session()->get('2fa:expires_at');

        // Check if using a recovery code
        $recoveryCodes = json_decode($user->two_factor_recovery_codes, true) ?? [];
        $submittedCode = trim($request->code);

        if (in_array($submittedCode, $recoveryCodes)) {
            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$submittedCode]));
            $user->update([
                'two_factor_recovery_codes' => json_encode($recoveryCodes),
            ]);

            $this->completeTwoFactorLogin($request, $user);
            return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
        }

        // Check OTP code
        if ($submittedCode !== $sessionCode || now()->greaterThan($expiresAt)) {
            return back()->withErrors(['code' => 'The code is invalid or has expired.']);
        }

        $this->completeTwoFactorLogin($request, $user);
        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    /**
     * Resend the 2FA OTP code via email.
     */
    public function resend(Request $request)
    {
        $userId = $request->session()->get('2fa:user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $user->notify(new TwoFactorCodeNotification($code));
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'code' => 'Unable to resend your verification code. Please check your internet connection and try again.',
            ]);
        }

        $request->session()->put('2fa:code', $code);
        $request->session()->put('2fa:expires_at', now()->addMinutes(10));

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    /**
     * Enable 2FA for the authenticated user.
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        // Generate 8 recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = Str::random(10);
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => Str::random(32),
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        try {
            $user->notify(new TwoFactorEnabledNotification());
        } catch (Throwable $e) {
            report($e);

            return back()
                ->with('status', '2fa-enabled')
                ->with('recoveryCodes', $recoveryCodes)
                ->with('error', 'Two-factor authentication was enabled, but we could not send the confirmation email.');
        }

        return back()->with('status', '2fa-enabled')->with('recoveryCodes', $recoveryCodes);
    }

    /**
     * Disable 2FA for the authenticated user.
     */
    public function disable(Request $request)
    {
        $request->user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        try {
            $request->user()->notify(new TwoFactorDisabledNotification());
        } catch (Throwable $e) {
            report($e);

            return back()->with('status', '2fa-disabled')
                ->with('error', 'Two-factor authentication was disabled, but we could not send the confirmation email.');
        }

        return back()->with('status', '2fa-disabled');
    }

    /**
     * Complete the 2FA login: log the user in and clean up session.
     */
    private function completeTwoFactorLogin(Request $request, $user)
    {
        $remember = $request->session()->get('2fa:remember', false);

        $request->session()->forget(['2fa:user_id', '2fa:code', '2fa:expires_at', '2fa:remember']);
        $request->session()->regenerate();

        auth()->login($user, $remember);
    }
}
