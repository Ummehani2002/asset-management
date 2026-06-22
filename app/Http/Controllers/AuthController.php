<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ActivityLog;
use App\Rules\AllowedEmailDomain;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        $user = User::whereRaw('LOWER(username) = ?', [strtolower($username)])->first();

        if (!$user) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($username)])->first();
        }

        if ($user && Hash::check($password, $user->password)) {
            Auth::login($user);

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            ActivityLog::log('login', 'Logged in', null, null, ['username' => $user->username ?? $user->email]);
            return redirect()->route('dashboard');
        }

        ActivityLog::log('login_failed', 'Failed login attempt', null, null, ['username' => $username]);
        return back()->withErrors(['username' => 'Invalid username or password.']);
    }

    public function showRegisterForm()
    {
        try {
            return view('auth.register');
        } catch (\Exception $e) {
            Log::error('Error showing register form: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['error' => 'Unable to load registration form. Please try again later.']);
        }
    }

    public function register(Request $request)
    {
        try {
            $hasUsernameColumn = false;
            try {
                $hasUsernameColumn = Schema::hasTable('users') && Schema::hasColumn('users', 'username');
            } catch (\Exception $e) {
                Log::warning('Could not check for username column: ' . $e->getMessage());
            }

            $validationRules = [
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', 'unique:users', new AllowedEmailDomain],
                'password' => 'required|min:8|confirmed',
            ];

            if ($hasUsernameColumn) {
                $validationRules['username'] = 'required|unique:users';
            }

            $request->validate($validationRules);

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ];

            if ($hasUsernameColumn && $request->has('username')) {
                $userData['username'] = $request->username;
            }

            $user = User::create($userData);
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('login')
                ->with('success', 'Account created. Please verify your email before logging in.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Registration database error: ' . $e->getMessage());

            if (str_contains($e->getMessage(), 'no such column: username') ||
                str_contains($e->getMessage(), "Unknown column 'username'") ||
                str_contains($e->getMessage(), "Column 'username' cannot be null")) {
                return back()->withErrors(['error' => 'Database migration required. Please run: php artisan migrate --force'])->withInput();
            }

            return back()->withErrors(['error' => 'Database error occurred. Please contact administrator.'])->withInput();
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred during registration. Please try again.'])->withInput();
        }
    }

    public function showVerificationNotice()
    {
        return view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Email verified successfully.');
    }

    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'A new verification link has been sent to your email address.');
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            ActivityLog::log('logout', 'Logged out', null, null, ['username' => $user->username ?? $user->email]);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
