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
            'identifier' => 'required|string',
            'password' => 'required',
        ]);

        try {
            $result = $this->authService->login(
                $request->identifier,
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
