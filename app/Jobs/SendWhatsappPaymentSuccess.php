<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappPaymentSuccess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
    ){}

    public function handle(FonnteService $fonnteService): void
    {
        $transaction = $this->transaction;

        $transaction->loadMissing(['user', 'classroom', 'package']);

        $user = $transaction->user;
        $classroom = $transaction->classroom;
        $package = $transaction->package;

        if (!$user || !$user->phone) {
            Log::info("Job Skipped: User {$user?->name} tidak punya nomor HP.");
            return;
        }

        $expiredDate = Carbon::parse($package->expired_at)->translatedFormat('d M Y');

        $message = "*InfoKelas: Pembayaran Berhasil!* ✅\n\n"
            . "Halo *{$user->name}*,\n"
            . "Pembayaran untuk paket *{$package->name}* telah disetujui.\n\n"
            . "Kelas: {$classroom->name}\n"
            . "Masa Aktif: s/d {$expiredDate}\n\n"
            . "Sekarang Anda bisa mengelola materi, tugas, dan jadwal kembali.\n"
            . "Terima kasih telah berlangganan!";

        $fonnteService->sendMessage($user->phone, $message);
    }
}
