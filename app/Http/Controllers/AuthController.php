<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MailjetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected MailjetService $mailjetService;

    public function __construct(MailjetService $mailjetService)
    {
        $this->mailjetService = $mailjetService;
    }

    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'sometimes|in:client,admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'client',
        ]);

        $token = JWTAuth::fromUser($user);

        // Enviar correo de bienvenida
        try {
            $this->mailjetService->sendWelcomeEmail($user);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // No fallar el registro si el correo falla
        }

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 201);
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    /**
     * Logout user (invalidate token).
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to logout',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh();

            return response()->json([
                'message' => 'Token refreshed',
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to refresh token',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'user' => auth()->user(),
        ]);
    }
}
