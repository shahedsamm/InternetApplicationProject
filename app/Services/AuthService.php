<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $data)
    {
        $user = User::query()->where('email', $data['email'])->firstOrFail();

        if (!$user->hasRole('admin')) {
            return 400;
        }
        if (!Hash::check($data['password'], $user->password)) {
            return 400;
        }
        $token = $user->createToken('admin_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(){
        return auth()->user()->currentAccessToken()->delete();
    }

}
