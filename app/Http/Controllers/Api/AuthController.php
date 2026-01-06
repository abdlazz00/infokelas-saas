<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Exception;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login API (Support Email / NIM)
     */
    public function login(Request $request)
    {
        $request->validate([
            // Kita ubah validasi agar tidak wajib format email, karena bisa jadi NIM
            'identifier' => 'required|string',
            'password' => 'required',
        ]);

        try {
            $result = $this->authService->login(
                $request->identifier, // Kirim input identifier (bisa email/nim)
                $request->password,
                'mahasiswa'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => $result,
            ]);

        } catch (Exception $e) {
            $statusCode = $e->getCode();
            $statusCode = ($statusCode >= 100 && $statusCode < 600) ? $statusCode : 401;

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    // ... method logout dan me tetap sama ...
    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());
            return response()->json(['status' => 'success', 'message' => 'Logout berhasil']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal logout'], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    }
}
