<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

        $user->setAttribute('company_name', $user->company ? $user->company->name : null);

        // Fetch funnels related to the user's company
        $funnels = Funnel::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->get()
            ->map(function ($funnel) {
                $funnel->nleads = $funnel->totalLeadsCount();
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
        $user = $request->user();

        $user->setAttribute('company_name', $user->company ? $user->company->name : null);

        return response()->json($user);
    }


    /**
     * Update user profile (name, phone, profile_pic, bg_pic)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'phone'       => 'sometimes|string|max:20',
            'profile_pic' => 'sometimes|nullable|file|image|max:2048', // max 2MB
            'bg_pic'      => 'sometimes|nullable|file|image|max:4096', // max 4MB
        ]);

        // Handle profile_pic upload
        if ($request->hasFile('profile_pic')) {
            // Delete old profile pic if exists
            if ($user->profile_pic && Storage::disk('public')->exists($user->profile_pic)) {
                Storage::disk('public')->delete($user->profile_pic);
            }

            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            
            $user->profile_pic = $path;
        }

        // Handle bg_pic upload
        if ($request->hasFile('bg_pic')) {
            // Delete old background pic if exists
            if ($user->bg_pic && Storage::disk('public')->exists($user->bg_pic)) {
                Storage::disk('public')->delete($user->bg_pic);
            }

            $bg_path = $request->file('bg_pic')->store('bg_pics', 'public');

            $user->bg_pic = $bg_path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $user
        ]);
    }

    /**
     * Reset password (requires old password + new password)
     */
    public function resetPassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed', 
            // requires new_password_confirmation field in request
        ]);

        if (!Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['The provided password does not match our records.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }

}
