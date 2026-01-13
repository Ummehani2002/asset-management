<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        // Attempt login using username or email
        $credentials = $request->only('username', 'password');

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])
            || Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']])) {
            return redirect()->route('dashboard')->with('success', 'Logged in successfully');
        }

        return back()->withErrors(['username' => 'Invalid username or password.']);
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        try {
            // Check if users table exists and has username column
            if (!Schema::hasTable('users')) {
                Log::error('Users table does not exist');
                return back()->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate'])->withInput();
            }
            
            if (!Schema::hasColumn('users', 'username')) {
                Log::error('Username column does not exist in users table');
                return back()->withErrors(['error' => 'Database column missing. Please run migrations: php artisan migrate'])->withInput();
            }
            
            $request->validate([
                'name' => 'required',
                'username' => 'required|unique:users',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6|confirmed'
            ]);

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            return redirect()->route('login')->with('success', 'User registered successfully. Please login.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show field-specific errors
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors
            Log::error('Registration database error: ' . $e->getMessage());
            $errorMessage = 'Database error: ' . $e->getMessage();
            // Show more helpful message for common issues
            if (str_contains($e->getMessage(), 'no such column: username')) {
                $errorMessage = 'Username column missing. Please run: php artisan migrate';
            } elseif (str_contains($e->getMessage(), 'no such table: users')) {
                $errorMessage = 'Users table missing. Please run: php artisan migrate';
            }
            return back()->withErrors(['error' => $errorMessage])->withInput();
        } catch (\Exception $e) {
            // Handle other errors
            Log::error('Registration error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()])->withInput();
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
