<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Ensure the authenticated user is only acting on their own account.
     */
    private function authorizeSelf(Request $request, User $user): void
    {
        abort_unless($request->user()->id === $user->id, 403, 'Forbidden');
    }

    /**
     * @OA\Get(
     *     path="/api/users/{user}",
     *     summary="Get a user's profile (self only)",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *     @OA\Response(response=200, description="User profile", @OA\JsonContent(ref="#/components/schemas/UserResource")),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     *
     * Display the specified user (self only).
     */
    public function show(Request $request, User $user)
    {
        $this->authorizeSelf($request, $user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{user}",
     *     summary="Update a user's name or email (self only)",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Jane Smith"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane.smith@example.com")
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Jane Smith"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane.smith@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated user", @OA\JsonContent(ref="#/components/schemas/UserResource")),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     *
     * Update the specified user's name/email (self only).
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeSelf($request, $user);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{user}/password",
     *     summary="Update a user's password (self only)",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer"), example=1),
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
     *     @OA\Response(response=403, description="Forbidden")
     * )
     *
     * Update the specified user's password (self only).
     */
    public function updatePassword(Request $request, User $user)
    {
        $this->authorizeSelf($request, $user);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 401);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password successfully updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{user}",
     *     summary="Delete a user account (self only)",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *     @OA\Response(response=204, description="Account deleted"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     *
     * Remove the specified user (self only).
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorizeSelf($request, $user);

        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
