<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login user and issue Sanctum token
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            return response()->json(['message' => 'User account is inactive'], 403);
        }

        $user->updateLastLogin();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Fetch funnels related to the user's company
        $funnels = Funnel::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->get()
            ->map(function ($funnel) {
                // Add random leads count (for now)
                $funnel->nleads = rand(10, 200);
                return $funnel;
            });

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
            'funnels'      => $funnels,
        ]);
    }


    /**
     * Logout (revoke tokens)
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh token (delete old & issue new one)
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // delete old tokens
        $user->tokens()->delete();

        // create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    /**
     * Get current user
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate(User::updateValidationRules($user->id));

        // if password is not provided, remove it from update
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user,
        ]);
    }
}
