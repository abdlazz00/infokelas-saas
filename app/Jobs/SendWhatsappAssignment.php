<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendWhatsappAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Assignment $assignment
    ) {}

    public function handle(FonnteService $fonnteService): void
    {
        // Load Relasi
        $this->assignment->loadMissing(['classroom', 'classroom.wa_group', 'subject']);
        $record = $this->assignment;
        $classroom = $record->classroom;

        // Cek Target
        $target = $classroom->wa_group?->jid;

        if (!$target) {
            Log::info("Job Skipped: Kelas {$classroom->name} belum punya WA Group.");
            return;
        }

        // Format Pesan (Tanpa Emoji)
        $message = "*TUGAS BARU*\n"
            . "---------------------------\n"
            . "*Mata Kuliah:* {$record->subject->name}\n"
            . "*Judul:* {$record->title}\n\n"
            . "*Batas Waktu:*\n"
            . $record->deadline->format('d M Y') . " (Pukul " . $record->deadline->format('H:i') . " WIB)\n\n"
            . "*Deskripsi Singkat:*\n"
            . Str::limit(strip_tags($record->description), 150) . "\n\n"
            . "---------------------------\n"
            . "Silakan cek detail lengkap di aplikasi.";

        // Kirim
        $fonnteService->sendMessage($target, $message);
    }
}
