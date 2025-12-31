<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $announcement;

    /**
     * Terima data announcement saat Job dibuat
     */
    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    /**
     * Eksekusi Job (Jalan di Background)
     */
    public function handle(): void
    {
        $record = $this->announcement;

        Log::info("Job Queue Berjalan: Mengirim WA untuk Pengumuman ID: {$record->id}");

        $fonnte = new FonnteService();

        // 1. Logika Emoji untuk SEMUA tipe
        $emoji = match ($record->type) {
            'danger' => '🚨',   // Merah/Darurat
            'warning' => '⚠️',  // Kuning/Penting
            'info' => 'ℹ️',     // Biru/Informasi
            default => '📢',
        };

        // 2. Format Header Label
        $label = match ($record->type) {
            'danger' => 'DARURAT',
            'warning' => 'PENTING',
            'info' => 'INFORMASI',
            default => 'PENGUMUMAN',
        };

        // 3. Susun Pesan
        $message = "*{$emoji} {$label} {$emoji}*\n\n"
            . "*{$record->title}*\n\n"
            . "{$record->content}\n\n"
            . "-----------------------------\n"
            . "Diterbitkan oleh: {$record->author->name}\n"
            . "Tanggal: " . $record->created_at->format('d M Y H:i');

        // 4. Kirim ke Fonnte
        // (Pastikan wa_group_id ada isinya sebelum kirim)
        if ($record->wa_group_id) {
            $fonnte->sendMessage($record->wa_group_id, $message);
            Log::info("Job Queue Selesai: Pesan terkirim ke {$record->wa_group_id}");
        } else {
            Log::warning("Job Queue Skipped: Tidak ada Group ID");
        }
    }
}
