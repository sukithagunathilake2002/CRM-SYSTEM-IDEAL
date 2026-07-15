<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Log the request for debugging
        Log::info('Login attempt', ['email' => $request->email]);

        // Validate the request - NO ROLE REQUIRED
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Prepare credentials - NO ROLE
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        Log::info('Attempting login with credentials', ['email' => $credentials['email']]);

        // Attempt login - system will find user by email and password only
        if (!Auth::attempt($credentials)) {
            Log::warning('Login failed', ['email' => $credentials['email']]);
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Get the authenticated user
        $user = Auth::user();
        Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role]);

        // Create a token for the user using Sanctum
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->role_label,
                'phone' => $user->phone,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->role_label,
            'phone' => $user->phone,
        ]);
    }
}