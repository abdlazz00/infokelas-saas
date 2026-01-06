<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim Pesan WhatsApp via Fonnte
     */
    public function sendMessage($target, $message)
    {
        // ✅ UPDATE: Ambil Token dari Config (Best Practice)
        // Ini akan membaca dari config/services.php -> array fonnte -> key token
        $token = config('services.fonnte.token');

        if (empty($token)) {
            // Ubah pesan log agar lebih akurat
            Log::error('Fonnte Token kosong. Cek config/services.php atau .env Anda.');
            return false;
        }

        // Format nomor hanya jika BUKAN Group ID
        if (!str_contains($target, '@')) {
            $target = $this->formatPhoneNumber($target);
        }

        try {
            // ✅ UPDATE: Ambil Endpoint dari Config
            // Jika tidak ada di config, default ke 'https://api.fonnte.com/send'
            $endpoint = config('services.fonnte.endpoint', 'https://api.fonnte.com/send');

            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post($endpoint, [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Opsional jika nomor lokal tidak pakai 62
            ]);

            // Log Response Fonnte untuk debugging
            Log::info('Fonnte API Response:', $response->json());

            if ($response->successful()) {
                return $response->json();
            } else {
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Fonnte Connection Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Format Nomor HP jadi format internasional (62xxx)
     */
    private function formatPhoneNumber($number)
    {
        $number = trim($number);
        $number = preg_replace('/[^0-9]/', '', $number);

        if (substr($number, 0, 2) === '08') {
            return '62' . substr($number, 1);
        }

        if (substr($number, 0, 1) === '8') {
            return '62' . $number;
        }

        return $number;
    }

    /**
     * Ambil daftar Group WA dari Akun Terhubung
     */
    public function fetchGroups()
    {
        // ✅ UPDATE: Gunakan config di sini juga
        $token = config('services.fonnte.token');

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/fetch-group');

            $result = $response->json();

            if (isset($result['status']) && $result['status'] == true) {
                if (empty($result['data'])) return [];
                return collect($result['data'])->pluck('name', 'id')->toArray();
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Fonnte Fetch Group Error: ' . $e->getMessage());
            return [];
        }
    }
}
