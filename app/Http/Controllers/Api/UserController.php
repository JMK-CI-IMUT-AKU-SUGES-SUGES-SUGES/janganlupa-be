<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UserController extends Controller
{
    public function me() 
    {
        return response()->json([
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => 'User fetched successfully!',
            ],
            'data' => [
                'user' => auth('api')->user(),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $data = $request->only([
            'name', 'slug', 'email', 'role_label', 'focus', 'timezone', 'status', 'avatar_url'
        ]);

        if (array_key_exists('slug', $data)) {
            $data['slug'] = User::normalizeSlug($data['slug']);
        }

        if (array_key_exists('email', $data)) {
            $data['email'] = strtolower(trim((string) $data['email']));
        }

        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if (User::slugExists($value, $user->id)) {
                        $fail('Slug sudah digunakan.');
                    }
                },
            ],
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'role_label' => 'sometimes|string|max:255',
            'focus' => 'sometimes|string',
            'timezone' => 'sometimes|string',
            'status' => 'sometimes|string',
            'avatar_url' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user->update($data);

        return response()->json([
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => 'User profile updated successfully!',
            ],
            'data' => [
                'user' => $user,
            ],
        ]);
    }
}
