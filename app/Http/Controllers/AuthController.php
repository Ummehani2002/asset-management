<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\User;

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

        // Case-insensitive username lookup
        // First try to find user by username (case-insensitive)
        $user = User::whereRaw('LOWER(username) = ?', [strtolower($username)])->first();
        
        // If not found by username, try email (case-insensitive)
        if (!$user) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($username)])->first();
        }

        // If user found, verify password
        if ($user && Hash::check($password, $user->password)) {
            Auth::login($user);
            return redirect()->route('dashboard');
        }

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
            // Check if username column exists before validating
            $hasUsernameColumn = false;
            try {
                $hasUsernameColumn = Schema::hasTable('users') && Schema::hasColumn('users', 'username');
            } catch (\Exception $e) {
                // If we can't check the schema, assume username column doesn't exist
                Log::warning('Could not check for username column: ' . $e->getMessage());
            }
            
            $validationRules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
            ];

            // Only validate username if the column exists
            if ($hasUsernameColumn) {
                $validationRules['username'] = 'required|unique:users';
            }
            
            $request->validate($validationRules);

            $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
            ];
            
            // Only add username if the column exists
            if ($hasUsernameColumn && $request->has('username')) {
                $userData['username'] = $request->username;
            }

            User::create($userData);

        return redirect()->route('login')->with('success', 'User registered successfully. Please login.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show field-specific errors
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors
            Log::error('Registration database error: ' . $e->getMessage());
            
            // Check for specific database errors
            if (str_contains($e->getMessage(), 'no such column: username') || 
                str_contains($e->getMessage(), "Unknown column 'username'") ||
                str_contains($e->getMessage(), "Column 'username' cannot be null")) {
                return back()->withErrors(['error' => 'Database migration required. Please run: php artisan migrate --force'])->withInput();
            }
            
            return back()->withErrors(['error' => 'Database error occurred. Please contact administrator.'])->withInput();
        } catch (\Exception $e) {
            // Handle other errors
            Log::error('Registration error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred during registration. Please try again.'])->withInput();
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
