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
use Illuminate\Support\Facades\DB;
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
            $user = Auth::user();
            if ($user->isSuperAdmin()) {
                $hotels = Hotel::where('is_active', true)->orderBy('name')->get();
                return view('auth.register', compact('hotels'));
            } elseif ($user->isManager()) {
                $hotels = Hotel::where('is_active', true)->where('id', $user->hotel_id)->get();
                return view('auth.register', compact('hotels'));
            } else {
                return redirect()->route('dashboard')->with('error', 'Unauthorized: Only administrators and property managers can register new staff.');
            }
        }

        $hotels = Hotel::where('is_active', true)->orderBy('name')->get();
        return view('auth.register', compact('hotels'));
    }

    public function register(Request $request)
    {
        $isAuthenticatedAdmin = Auth::check();
        $currentUser = Auth::user();

        if ($isAuthenticatedAdmin) {
            if (!$currentUser->isSuperAdmin() && !$currentUser->isManager()) {
                abort(403, 'Unauthorized.');
            }
        } else {
            // Bot Verification with mandatory human challenge on unauthenticated public registration
            $botCheck = $this->botDefenseService->verifyForm($request, true);
            if (!$botCheck['success']) {
                throw ValidationException::withMessages([
                    '_cs_challenge_answer' => [$botCheck['message']],
                ]);
            }
        }

        $messages = [
            'email.unique' => 'This email address is already registered. Please sign in or use another email.',
            'hotel_id.required' => 'An assigned property is required for staff registration.',
            'hotel_id.exists' => 'The selected property does not exist or is inactive.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
        ];

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'hotel_id' => 'required|exists:hotels,id',
            'role' => 'required|in:manager,frontdesk,housekeeping,revenue,qa',
            'locale' => 'required|in:en,ja',
        ], $messages);

        // Verify hotel is active
        $hotel = Hotel::where('id', $request->hotel_id)->where('is_active', true)->first();
        if (!$hotel) {
            throw ValidationException::withMessages([
                'hotel_id' => ['The selected property does not exist or is inactive.'],
            ]);
        }

        // Multi-tenancy check: If manager, cannot assign to another hotel
        if ($isAuthenticatedAdmin && !$currentUser->isSuperAdmin() && $currentUser->hotel_id !== intval($request->hotel_id)) {
            abort(403, 'Managers may only register staff for their assigned property.');
        }

        $user = DB::transaction(function () use ($request, $hotel) {
            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'hotel_id' => $hotel->id,
                'role' => $request->role,
                'locale' => $request->locale,
                'is_active' => true,
                'is_suspended' => false,
                'email_verified_at' => now(),
                'last_login_at' => null,
            ]);
        });

        AuditLog::record('auth.register', $user, null, [
            'creator_id' => $isAuthenticatedAdmin ? $currentUser->id : null,
            'hotel_id' => $user->hotel_id,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);
        SecurityIncident::log('auth.staff_registered', 'low', [
            'email' => $user->email,
            'hotel_id' => $user->hotel_id,
            'role' => $user->role,
        ], $request);

        if ($isAuthenticatedAdmin) {
            return redirect()->route('staff.index')->with('success', "Staff account for [{$user->name}] successfully created for {$hotel->name}.");
        }

        // Public self-registration: auto-login the new user and redirect to dashboard.
        // This provides a seamless onboarding experience; the session and hotel context
        // are set immediately so the user lands on a fully-configured dashboard.
        Auth::login($user);
        session(['current_hotel_id' => $user->hotel_id]);
        session(['locale' => $user->locale]);

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
