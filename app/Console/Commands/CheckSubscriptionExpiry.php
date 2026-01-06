<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Services\FonnteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionExpiry extends Command
{
    /**
     * Nama command yang akan dipanggil scheduler
     */
    protected $signature = 'subscription:check-expiry';

    /**
     * Deskripsi command
     */
    protected $description = 'Cek kelas yang masa aktifnya habis dan nonaktifkan';

    /**
     * Eksekusi logic
     */
    public function handle(FonnteService $fonnteService)
    {
        $this->info('Memulai pengecekan masa aktif kelas...');

        // 1. Ambil kelas yang Aktif TAPI Expired-nya sudah lewat
        // Gunakan chunk agar hemat memori jika ada ribuan kelas
        Classroom::where('subscription_status', 'active')
            ->where('expired_at', '<', now())
            ->chunk(100, function ($classrooms) use ($fonnteService) {

                foreach ($classrooms as $classroom) {
                    // Load Owner (Guru/Admin Kelas)
                    $classroom->loadMissing('teacher');
                    $teacher = $classroom->teacher;

                    try {
                        // 2. Nonaktifkan Kelas
                        $classroom->update([
                            'subscription_status' => 'expired',
                            'is_active' => false, // Siswa tidak bisa akses lagi
                        ]);

                        $this->info("Kelas '{$classroom->name}' telah dinonaktifkan.");

                        // 3. Kirim Notifikasi WA ke Admin Kelas
                        if ($teacher && $teacher->phone) {
                            $message = "*PERINGATAN: MASA AKTIF HABIS*\n"
                                . "-----------------------------\n"
                                . "Halo *{$teacher->name}*,\n\n"
                                . "Masa aktif kelas *{$classroom->name}* telah berakhir hari ini.\n\n"
                                . "Siswa tidak dapat mengakses materi atau presensi sampai Anda melakukan perpanjangan paket.\n\n"
                                . "Silakan lakukan pembelian paket baru melalui menu Transaksi.\n"
                                . "-----------------------------";

                            $fonnteService->sendMessage($teacher->phone, $message);
                        }

                    } catch (\Exception $e) {
                        Log::error("Gagal memproses expiry kelas ID {$classroom->id}: " . $e->getMessage());
                    }
                }
            });

        $this->info('Pengecekan selesai.');
    }
}
