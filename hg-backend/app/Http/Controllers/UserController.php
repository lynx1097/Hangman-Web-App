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
