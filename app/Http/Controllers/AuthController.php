<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\SecurityIncident;
use App\Models\User;
use App\Services\BotDefenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected BotDefenseService $botDefenseService;

    public function __construct(BotDefenseService $botDefenseService)
    {
        $this->botDefenseService = $botDefenseService;
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1. ClearShield Bot & Human Verification Check
        $botCheck = $this->botDefenseService->verifyForm($request, false);
        if (!$botCheck['success']) {
            throw ValidationException::withMessages([
                '_cs_challenge_answer' => [$botCheck['message']],
            ]);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // 2. Check User-Specific Account Lockout
        $userCandidate = User::where('email', $request->email)->first();
        if ($userCandidate && $userCandidate->isLockedOut()) {
            $remainingMins = Carbon::now()->diffInMinutes($userCandidate->lockout_until);
            SecurityIncident::log('auth.locked_out_attempt', 'high', [
                'email' => $request->email,
                'remaining_minutes' => $remainingMins,
            ], $request);

            throw ValidationException::withMessages([
                'email' => [__('clearstay.security.account_locked_message', ['minutes' => max(1, $remainingMins)])],
            ]);
        }

        // 3. Login Rate Limiting (5 attempts per 60 seconds)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditLog::record('auth.rate_limited', null, null, ['email' => $request->email, 'seconds' => $seconds]);
            SecurityIncident::log('auth.rate_limited', 'high', ['email' => $request->email, 'seconds' => $seconds], $request);

            throw ValidationException::withMessages([
                'email' => [__('clearstay.auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::validate($credentials)) {
            $user = User::where('email', $request->email)->first();

            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'email' => [__('clearstay.auth.account_inactive')],
                ]);
            }

            // 4. Two-Factor Authentication (2FA) Check
            if ($user->two_factor_enabled) {
                RateLimiter::clear($throttleKey);
                session([
                    'auth_2fa_user_id' => $user->id,
                    'auth_2fa_remember' => $remember,
                ]);

                AuditLog::record('auth.2fa_prompted', $user);
                return redirect()->route('login.2fa');
            }

            // Regular successful login
            Auth::attempt($credentials, $remember);
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            // Reset failed counter & record login telemetry
            $user->failed_login_attempts = 0;
            $user->lockout_until = null;
            $user->last_login_at = now();
            $user->last_login_ip = $request->ip();
            $user->save();

            // Set tenant hotel in session
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

            AuditLog::record('auth.login', $user);

            return redirect()->intended(route('dashboard'));
        }

        // Failed login handling
        RateLimiter::hit($throttleKey, 60);

        if ($userCandidate) {
            $userCandidate->increment('failed_login_attempts');
            if ($userCandidate->failed_login_attempts >= 5) {
                $userCandidate->update(['lockout_until' => now()->addMinutes(15)]);
                SecurityIncident::log('auth.account_locked', 'critical', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                ], $request);
            }
        }

        AuditLog::record('auth.failed', null, null, ['email' => $request->email]);
        SecurityIncident::log('auth.failed', 'medium', ['email' => $request->email], $request);

        throw ValidationException::withMessages([
            'email' => [__('clearstay.auth.failed')],
        ]);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $hotels = Hotel::where('is_active', true)->get();
        return view('auth.register', compact('hotels'));
    }

    public function register(Request $request)
    {
        // Bot Verification with mandatory human challenge on public registration
        $botCheck = $this->botDefenseService->verifyForm($request, true);
        if (!$botCheck['success']) {
            throw ValidationException::withMessages([
                '_cs_challenge_answer' => [$botCheck['message']],
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users',
            'password' => 'required|min:8|confirmed',
            'hotel_id' => 'nullable|exists:hotels,id',
            'role' => 'required|in:manager,frontdesk,housekeeping',
            'locale' => 'required|in:en,ja',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'hotel_id' => $request->hotel_id,
            'role' => $request->role,
            'locale' => $request->locale,
            'is_active' => true,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        Auth::login($user);

        if ($user->hotel_id) {
            session(['current_hotel_id' => $user->hotel_id]);
        }
        session(['locale' => $user->locale]);

        AuditLog::record('auth.register', $user);
        SecurityIncident::log('auth.staff_registered', 'low', ['email' => $user->email], $request);

        return redirect()->route('dashboard')->with('success', __('clearstay.auth.registered_welcome'));
    }

    public function logout(Request $request)
    {
        AuditLog::record('auth.logout', Auth::user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', __('clearstay.auth.logged_out'));
    }

    /**
     * Switch hotel context for SuperAdmin / authorized staff.
     */
    public function switchHotel(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
        ]);

        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->hotel_id !== intval($request->hotel_id)) {
            abort(403, 'Cannot access this hotel property.');
        }

        session(['current_hotel_id' => $request->hotel_id]);

        return back()->with('success', __('clearstay.general.hotel_switched'));
    }
}
