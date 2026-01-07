<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService; // <--- Import Service Baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;

class ForgotPasswordController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * 1. REQUEST OTP
     */
    public function sendOtp(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);

        // Cari User
        $user = User::where('email', $request->identifier)
            ->orWhere('nim', $request->identifier)
            ->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }

        try {
            // Panggil Service untuk logic generate & kirim
            $this->otpService->sendOtp($user, $request->identifier);

            return response()->json([
                'status' => 'success',
                'message' => 'Kode OTP berhasil dikirim ke WhatsApp Anda.',
            ]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * 2. RESET PASSWORD
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp'        => 'required|numeric',
            'password'   => 'required|min:6|confirmed',
        ]);

        // Validasi OTP via Service
        $isValid = $this->otpService->validateOtp($request->identifier, $request->otp);

        if (!$isValid) {
            return response()->json(['status' => 'error', 'message' => 'Kode OTP salah atau kadaluarsa.'], 400);
        }

        // Cari User & Update Password
        $user = User::where('email', $request->identifier)
            ->orWhere('nim', $request->identifier)
            ->first();

        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Password berhasil diubah. Silakan login.',
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'User error.'], 500);
    }
}
