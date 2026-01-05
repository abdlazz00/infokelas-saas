<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthService
{
    public function login(string $identifier, string $password, ?string $requiredRole = null): array
    {
        $user = User::where('email', $identifier)
            ->orWhere('nim', $identifier)
            ->first();

        // 2. Validasi Kredensial
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial (Email/NIM atau Password) salah.'],
            ]);
        }

        // 3. Validasi Role
        if ($requiredRole && $user->role !== $requiredRole) {
            throw new Exception("Akses ditolak. Hanya akun $requiredRole yang diizinkan.", 403);
        }

        // 4. Validasi Status Aktif
        if (! $user->is_active) {
            throw new Exception("Akun Anda dinonaktifkan. Silakan hubungi Admin.", 403);
        }

        // 5. Generate Token
        $token = $user->createToken('api_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
