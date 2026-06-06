<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $payload = $request->all();
        $payload['slug'] = User::normalizeSlug($payload['slug'] ?? null);
        $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));

        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (User::slugExists($value)) {
                        $fail('Slug sudah digunakan.');
                    }
                },
            ],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, 'User created successfully!', $user);
    }

    public function login(Request $request)
    {
        $payload = $request->all();
        $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));

        $validator = Validator::make($payload, [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $credentials = [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ];

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = User::where('email', $credentials['email'])->first();

        return $this->respondWithToken($token, 'User logged in successfully.', $user);
    }

    public function logout()
    {
        JWTAuth::invalidate(true);

        return response()->json([
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => 'Successfully logged out',
            ],
            'data' => [],
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh(), 'Token refreshed successfully.');
    }

    protected function respondWithToken($token, $message, $user = null)
    {
        return response()->json([
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => $message,
            ],
            'data' => [
                'user' => $user ?? JWTAuth::user(),
                'access_token' => [
                    'token' => $token,
                    'type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ],
        ]);
    }
}
