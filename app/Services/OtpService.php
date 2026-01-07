<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class OtpService
{
    protected $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    /**
     * Generate OTP, Simpan ke Cache, dan Kirim WA
     */
    public function sendOtp(User $user, string $identifier)
    {
        // 1. Validasi Nomor HP
        if (!$user->phone) {
            throw new Exception('Nomor WhatsApp tidak terdaftar di akun ini.');
        }

        // 2. Generate OTP
        $otp = rand(100000, 999999);

        // 3. Simpan di Cache (Key pakai identifier yang diinput user)
        // Format Key: otp_reset_12345678 (NIM) atau otp_reset_email@test.com
        $cacheKey = 'otp_reset_' . $identifier;
        Cache::put($cacheKey, $otp, now()->addMinutes(5));

        // 4. Template Pesan WA
        $message = $this->getOtpMessageTemplate($user->name, $otp);

        // 5. Kirim via Fonnte
        try {
            $this->fonnteService->sendMessage($user->phone, $message);
        } catch (Exception $e) {
            Log::error("Gagal kirim OTP ke {$user->phone}: " . $e->getMessage());
            throw new Exception('Gagal mengirim pesan WhatsApp. Coba lagi nanti.');
        }

        return true;
    }

    /**
     * Validasi apakah OTP benar
     */
    public function validateOtp(string $identifier, string $otp): bool
    {
        $cacheKey = 'otp_reset_' . $identifier;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp != $otp) {
            return false;
        }

        // Hapus OTP setelah valid (One Time Use)
        Cache::forget($cacheKey);
        return true;
    }

    /**
     * Template Pesan WA (Private)
     */
    private function getOtpMessageTemplate($name, $otp)
    {
        return "*RESET PASSWORD INFOKELAS* \n\n"
            . "Halo *{$name}*,\n"
            . "Kode OTP Anda adalah: *{$otp}*\n\n"
            . "Kode ini berlaku selama 5 menit.\n"
            . "Jangan berikan kode ini kepada siapapun.";
    }
}
