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

    public function __construct(
        public Announcement $announcement
    ) {}

    public function handle(FonnteService $fonnteService): void
    {
        // 1. Load Relasi
        $this->announcement->loadMissing(['classroom', 'waGroup', 'author']);

        $record = $this->announcement;
        $classroom = $record->classroom;

        // 2. Cek Target (Langsung dari relasi waGroup di announcement)
        $target = $record->waGroup?->jid;

        if (!$target) {
            Log::info("Job Skipped: Pengumuman di kelas {$classroom->name} tidak ada target WA.");
            return;
        }

        // 3. Format Header Label (Tanpa Emoji)
        $label = match ($record->type) {
            'danger' => 'PENTING / DARURAT',
            'warning' => 'PERHATIAN',
            'info' => 'INFORMASI',
            default => 'PENGUMUMAN',
        };

        // 4. Susun Pesan
        $content = strip_tags($record->content); // Bersihkan HTML tags
        $author = $record->author->name ?? 'Dosen';
        $date = $record->created_at->format('d M Y H:i');

        $message = "*[{$label}]*\n"
            . "---------------------------\n"
            . "*{$record->title}*\n\n"
            . "{$content}\n\n"
            . "---------------------------\n"
            . "Kelas: {$classroom->name}\n"
            . "Oleh: {$author}\n"
            . "Waktu: {$date}";

        // 5. Kirim ke Group
        $fonnteService->sendMessage($target, $message);
    }
}
