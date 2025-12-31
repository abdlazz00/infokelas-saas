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
        $token = env('FONNTE_TOKEN');
        if (empty($token)) {
            Log::error('Fonnte Token belum disetting di .env');
            return false;
        }

        // PERBAIKAN: Format nomor hanya jika BUKAN Group ID
        // Group ID memiliki karakter '@', jadi kita cek dulu
        if (!str_contains($target, '@')) {
            $target = $this->formatPhoneNumber($target);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post(env('FONNTE_ENDPOINT'), [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
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
     * Hanya digunakan untuk nomor HP personal, bukan Group.
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
        $token = env('FONNTE_TOKEN');

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
