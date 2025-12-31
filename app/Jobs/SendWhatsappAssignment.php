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

    protected $assignment;

    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function handle(): void
    {
        $record = $this->assignment;
        $fonnte = new FonnteService();

        // Icon Buku / Tugas
        $emoji = '📝';

        // Format Pesan Tugas
        $message = "*{$emoji} TUGAS BARU {$emoji}*\n"
            . "-----------------------------\n\n"
            . "*Mata Kuliah:*\n"
            . "{$record->subject->name}\n\n"
            . "*Judul Tugas:*\n"
            . "{$record->title}\n\n"
            . "*Deadline:*\n"
            . "📅 " . $record->deadline->format('d M Y') . "\n"
            . "⏰ " . $record->deadline->format('H:i') . " WIB\n\n"
            . "*Deskripsi Singkat:*\n"
            . Str::limit($record->description, 100) . "\n\n"
            . "-----------------------------\n"
            . "Cek detail & lampiran di aplikasi Classmate.";

        if ($record->wa_group_id) {
            $fonnte->sendMessage($record->wa_group_id, $message);
            Log::info("WA Tugas terkirim ke {$record->wa_group_id}");
        }
    }
}
