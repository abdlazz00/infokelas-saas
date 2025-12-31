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

    protected $material;

    /**
     * Terima data material saat Job dibuat
     */
    public function __construct(Material $material)
    {
        $this->material = $material;
    }

    /**
     * Eksekusi Job (Jalan di Background)
     */
    public function handle(): void
    {
        $record = $this->material;
        $fonnte = new FonnteService();

        $frontendUrl = env('FRONTEND_URL');

        $directLink = "{$frontendUrl}/class/{$record->classroom_id}";

        $emoji = '📚';

        // 2. SUSUN PESAN
        $message = "*{$emoji} MATERI BARU DIUPLOAD {$emoji}*\n"
            . "-----------------------------\n\n"
            . "*Mata Kuliah:*\n"
            . "{$record->subject->name}\n\n"
            . "*Judul Materi:*\n"
            . "{$record->title}\n\n"
            . "*Link Akses Cepat:* \n"
            . "🔗 {$directLink}\n\n"
            . "-----------------------------\n"
            . "Silakan buka aplikasi untuk mengunduh file atau menonton video materi.";

        // 3. KIRIM PESAN
        if ($record->wa_group_id) {
            $fonnte->sendMessage($record->wa_group_id, $message);
            Log::info("WA Materi terkirim ke Group ID: {$record->wa_group_id}");
        } else {
            Log::warning("WA Materi batal: Tidak ada Group ID yang dipilih.");
        }
    }
}
