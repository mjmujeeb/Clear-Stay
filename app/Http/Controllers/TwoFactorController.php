<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\SecurityIncident;
use App\Models\User;
use App\Services\BotDefenseService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;
    protected BotDefenseService $botDefenseService;

    public function __construct(TwoFactorAuthService $twoFactorService, BotDefenseService $botDefenseService)
    {
        $this->twoFactorService = $twoFactorService;
        $this->botDefenseService = $botDefenseService;
    }

    /**
     * Show 2FA challenge input screen.
     */
    public function show()
    {
        $userId = session('auth_2fa_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        return view('auth.2fa', compact('user'));
    }

    /**
     * Verify submitted 2FA code or recovery code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = session('auth_2fa_user_id');
        $remember = session('auth_2fa_remember', false);

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        // Bot verification on 2FA form
        $botCheck = $this->botDefenseService->verifyForm($request, false);
        if (!$botCheck['success']) {
            throw ValidationException::withMessages([
                'code' => [$botCheck['message']],
            ]);
        }

        if ($this->twoFactorService->verify($user, $request->code)) {
            // Clear temporary 2FA session
            session()->forget(['auth_2fa_user_id', 'auth_2fa_remember']);

            Auth::login($user, $remember);
            $request->session()->regenerate();

            // Setup hotel tenant context
            if ($user->hotel_id) {
                session(['current_hotel_id' => $user->hotel_id]);
            } else {
                $firstHotel = Hotel::where('is_active', true)->first();
                if ($firstHotel) {
                    session(['current_hotel_id' => $firstHotel->id]);
                }
            }

            if ($user->locale) {
                session(['locale' => $user->locale]);
            }

            $user->failed_login_attempts = 0;
            $user->lockout_until = null;
            $user->last_login_at = now();
            $user->last_login_ip = $request->ip();
            $user->save();

            AuditLog::record('auth.2fa_login_success', $user);

            return redirect()->intended(route('dashboard'))
                ->with('success', __('clearstay.security.two_factor_verified_welcome'));
        }

        SecurityIncident::log('auth.2fa_invalid_attempt', 'high', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ], $request, false);

        throw ValidationException::withMessages([
            'code' => [__('clearstay.security.two_factor_invalid_code')],
        ]);
    }
}
