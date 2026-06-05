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
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:users,slug,' . $user->id,
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

        $data = $request->only([
            'name', 'slug', 'email', 'role_label', 'focus', 'timezone', 'status', 'avatar_url'
        ]);

        if (isset($data['slug'])) {
            $data['slug'] = strtolower($data['slug']);
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
