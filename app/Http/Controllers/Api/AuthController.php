<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // <--- PERBAIKAN 1: Import Model User
use Illuminate\Support\Facades\Hash; // <--- PERBAIKAN 2: Import Hash untuk cek password

class AuthController extends Controller
{
    /**
     * Login API untuk Mahasiswa
     */
    public function login(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cari User berdasarkan Email
        $user = User::where('email', $request->email)->first();

        // 3. Cek apakah user ada & password benar
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // 4. CEK ROLE: Hanya 'mahasiswa' yang boleh login lewat API ini
        // (Super Admin & Dosen login lewat Web Admin Filament)
        if ($user->role !== 'mahasiswa') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya akun mahasiswa yang bisa login di aplikasi ini.',
            ], 403);
        }

        // 5. CEK STATUS AKTIF: Apakah akun diblokir/cuti?
        if (! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi Admin Akademik.',
            ], 403);
        }

        // 6. Login Sukses -> Buat Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [ // Perhatikan struktur data ini, di frontend harus menyesuaikan (response.data.data.user)
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * Logout API (Hapus Token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Cek User yang sedang login (Profile)
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    }
}
