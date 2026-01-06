<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappPaymentRejected implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
    ){}

    public function handle(FonnteService $fonnteService): void
    {
        $this->transaction->loadMissing(['user']);
        $user = $this->transaction->user;

        if(!$user || !$user->phone) {
            Log::info("Job Rejected Skipped: User {$user?->name} tidak punya nomor HP.");
            return;
        }

        $reason = $this->transaction->admin_note ?? '-';

        $message = "*InfoKelas: Pembayaran Ditolak* ❌\n\n"
            . "Halo *{$user->name}*,\n"
            . "Mohon maaf, transaksi Anda dengan ID #{$this->transaction->id} telah ditolak oleh Admin.\n\n"
            . "*Alasan Penolakan:*\n"
            . "_{$reason}_\n\n"
            . "Hubungi kami untuk bantuan lebih lanjut.";

        // 4. Kirim Pesan
        $fonnteService->sendMessage($user->phone, $message);
    }
}
