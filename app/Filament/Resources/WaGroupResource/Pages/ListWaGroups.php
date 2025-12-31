<?php

namespace App\Filament\Resources\WaGroupResource\Pages;

use App\Filament\Resources\WaGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaGroups extends ListRecords
{
    protected static string $resource = WaGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
