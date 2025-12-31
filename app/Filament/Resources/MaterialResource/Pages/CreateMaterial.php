<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use App\Jobs\SendWhatsappMaterial; // Import Job
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateMaterial extends CreateRecord
{
    protected static string $resource = MaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->wa_group_id) {
            SendWhatsappMaterial::dispatch($record);

            Notification::make()
                ->title('Materi Berhasil Diupload')
                ->body('Notifikasi WhatsApp sedang dikirim.')
                ->success()
                ->send();
        }
    }
}
