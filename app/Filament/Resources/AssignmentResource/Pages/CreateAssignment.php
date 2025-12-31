<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use App\Jobs\SendWhatsappAssignment; // <--- Import Job
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->wa_group_id) {
            SendWhatsappAssignment::dispatch($record);

            Notification::make()
                ->title('Tugas Berhasil Dibuat')
                ->body('Notifikasi WhatsApp sedang dikirim.')
                ->success()
                ->send();
        }
    }
}
