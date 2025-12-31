<?php

use App\Services\FonnteService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-wa', function () {
    $fonnte = new FonnteService();

    // GANTI NOMOR INI DENGAN NOMOR WA PRIBADI KAMU UNTUK TES
    $target = '085668348028';
    $pesan = "Halo! Ini tes notifikasi dari Classmate App via Fonnte Service. 🚀";

    $result = $fonnte->sendMessage($target, $pesan);

    if ($result) {
        return "Sukses! Cek WA kamu. Response: " . json_encode($result);
    } else {
        return "Gagal mengirim. Cek Log laravel (storage/logs/laravel.log)";
    }
});
