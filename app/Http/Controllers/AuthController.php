<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // Login rate limiting: 5 attempts per minute
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditLog::record('auth.rate_limited', null, null, ['email' => $request->email, 'seconds' => $seconds]);

            throw ValidationException::withMessages([
                'email' => [__('clearstay.auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => [__('clearstay.auth.account_inactive')],
                ]);
            }

            // Set tenant hotel in session
            if ($user->hotel_id) {
                session(['current_hotel_id' => $user->hotel_id]);
            } else {
                // If superadmin, default to first active hotel
                $firstHotel = Hotel::where('is_active', true)->first();
                if ($firstHotel) {
                    session(['current_hotel_id' => $firstHotel->id]);
                }
            }

            // Set user preferred locale
            if ($user->locale) {
                session(['locale' => $user->locale]);
            }

            AuditLog::record('auth.login', $user);

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        AuditLog::record('auth.failed', null, null, ['email' => $request->email]);

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
        ]);

        Auth::login($user);

        if ($user->hotel_id) {
            session(['current_hotel_id' => $user->hotel_id]);
        }
        session(['locale' => $user->locale]);

        AuditLog::record('auth.register', $user);

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
