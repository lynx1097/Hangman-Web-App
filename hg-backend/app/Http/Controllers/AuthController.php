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
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Register a new user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"name","email","password","password_confirmation"},
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="password", type="string", minLength=6, example="secret123"),
     *                 @OA\Property(property="password_confirmation", type="string", example="secret123")
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"name","email","password","password_confirmation"},
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="password", type="string", minLength=6, example="secret123"),
     *                 @OA\Property(property="password_confirmation", type="string", example="secret123")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        // Enhanced validation rules including password strength requirements
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(6)],
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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login and get a Bearer token",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email","password"},
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="password", type="string", example="secret123")
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"email","password"},
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="password", type="string", example="secret123")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
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
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Revoke the current Bearer token",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Logged out", @OA\JsonContent(@OA\Property(property="message", type="string", example="Successfully logged out"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
    /**
     * @OA\Put(
     *     path="/api/auth/password",
     *     summary="Change the authenticated user's password",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"current_password","password","password_confirmation"},
     *                 @OA\Property(property="current_password", type="string", example="oldPass1"),
     *                 @OA\Property(property="password", type="string", minLength=6, example="newPass1"),
     *                 @OA\Property(property="password_confirmation", type="string", example="newPass1")
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"current_password","password","password_confirmation"},
     *                 @OA\Property(property="current_password", type="string", example="oldPass1"),
     *                 @OA\Property(property="password", type="string", minLength=6, example="newPass1"),
     *                 @OA\Property(property="password_confirmation", type="string", example="newPass1")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password updated", @OA\JsonContent(@OA\Property(property="message", type="string", example="Password successfully updated"))),
     *     @OA\Response(response=401, description="Current password incorrect"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)],
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