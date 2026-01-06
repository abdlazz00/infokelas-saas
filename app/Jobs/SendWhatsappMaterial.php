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
        // Load Relasi
        $this->material->loadMissing(['classroom', 'classroom.wa_group', 'subject']);
        $record = $this->material;
        $classroom = $record->classroom;

        // Cek Target
        $target = $classroom->wa_group?->jid;

        if (!$target) {
            Log::info("Job Skipped: Kelas {$classroom->name} belum punya WA Group.");
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
            . "*Link Akses:*\n"
            . "{$directLink}\n\n"
            . "---------------------------\n"
            . "Silakan login aplikasi untuk mengunduh file atau materi.";

        // Kirim
        $fonnteService->sendMessage($target, $message);
    }
}
