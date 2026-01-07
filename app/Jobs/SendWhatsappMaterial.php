<?php

namespace App\Jobs;

use App\Models\Material;
use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsappMaterial implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Material $material
    ) {}

    public function handle(FonnteService $fonnteService): void
    {
        // 1. Load Relasi yang Benar
        // Kita load 'waGroup' langsung dari material, bukan dari classroom
        $this->material->loadMissing(['classroom', 'subject', 'waGroup']);

        $record = $this->material;

        // 2. Cek Target JID dari relasi waGroup di material
        $target = $record->waGroup?->jid;

        if (!$target) {
            Log::info("Job Skipped: Materi '{$record->title}' tidak di-set ke WA Group manapun.");
            return;
        }

        // Link Akses
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        // Arahkan ke halaman subject detail
        $directLink = "{$frontendUrl}/class/{$record->classroom_id}/subject/{$record->subject_id}";

        // Format Pesan (Tanpa Emoji)
        $message = "*MATERI BARU DIUPLOAD*\n"
            . "---------------------------\n"
            . "*Mata Kuliah:* {$record->subject->name}\n"
            . "*Judul Materi:* {$record->title}\n\n"
            . "*Deskripsi Materi:*\n"
            . "{$record->description}\n\n"
            . "---------------------------\n"
            . "Silakan login aplikasi untuk mengunduh file atau materi.";

        // Kirim
        $fonnteService->sendMessage($target, $message);
    }
}
