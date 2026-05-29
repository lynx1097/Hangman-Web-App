<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaderboardEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Enhanced validation rules including password strength requirements
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',  // Requires password_confirmation field
                Password::min(8)  // Minimum length of 8 characters
                    ->mixedCase()  // Requires both uppercase and lowercase letters
                    ->numbers()    // Requires at least one number
                    ->symbols()    // Requires at least one symbol
            ],
        ]);

        // Create user with hashed password
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Securely hash the password
        ]);

        // Initialize leaderboard entry for new user
        LeaderboardEntry::create([
            'user_id' => $user->id,
            'total_score' => 0,
            'games_won' => 0,
            'games_played' => 0
        ]);

        // Generate secure API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        // Validate login request
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Attempt authentication
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect'
            ], 401);
        }

        // Retrieve authenticated user
        $user = User::where('email', $request->email)->firstOrFail();
        
        // Delete any existing tokens if you want to enforce single device login
        // $user->tokens()->delete();
        
        // Generate new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
    public function isloggedin(Request $request)
    {
        $currtoken = $request->user()->currentAccessToken();
        if ($currtoken === NULL) {
            return response()->json([
                'message' => 'User is not logged in'
            ]);
        }
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        // Check if current password matches
        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 401);
        }

        // Update password
        $request->user()->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'message' => 'Password successfully updated'
        ]);
    }
}