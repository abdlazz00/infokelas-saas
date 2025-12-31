<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use App\Jobs\SendWhatsappAnnouncement; // <--- JANGAN LUPA IMPORT INI
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->wa_group_id) {
            SendWhatsappAnnouncement::dispatch($record);

            Log::info("Job Dispatched untuk Announcement ID: {$record->id}");

            Notification::make()
                ->title('Pengumuman Dibuat')
                ->body('Sedang dikirim ke group Whatsapp.')
                ->success()
                ->send();
        }
    }
}
