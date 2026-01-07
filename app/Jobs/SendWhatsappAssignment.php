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
        $this->assignment->loadMissing(['classroom', 'subject', 'waGroup']);
        $record = $this->assignment;

        // Cek Target
        $target = $record->waGroup?->jid;

        if (!$target) {
            Log::info("Job Skipped: Tugas '{$record->title}' tidak di-set ke WA Group manapun.");
            return;
        }

        // Format Pesan (Tanpa Emoji)
        $message = "*TUGAS BARU*\n"
            . "---------------------------\n"
            . "*Mata Kuliah:* {$record->subject->name}\n"
            . "*Judul:* {$record->title}\n\n"
            . "*Batas Waktu:*\n"
            . $record->deadline->format('d M Y') . " (Pukul " . $record->deadline->format('H:i') . " WIB)\n\n"
            . "*Deskripsi:*\n"
            . strip_tags($record->description) . "\n\n"
            . "---------------------------\n"
            . "Silakan cek detail lengkap di aplikasi.";

        // Kirim
        $fonnteService->sendMessage($target, $message);
    }
}
