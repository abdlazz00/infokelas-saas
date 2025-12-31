<?php

namespace App\Filament\Widgets;

use App\Models\Classroom;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ExpiringClassesWidget extends BaseWidget
{
    protected static ?string $heading = 'Monitoring Masa Aktif Kelas';
    protected static ?int $sort = 3;
//    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Classroom::query()
                    ->with(['teacher'])
                    ->where('is_active', true)
                    ->whereDate('expired_at', '<=', now()->addDays(7))
                    ->orderBy('expired_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Dosen')
                    ->description(fn (Classroom $record) => $record->teacher->email ?? '-'),

                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->description(fn (Classroom $record) => $record->expired_at->diffForHumans())
                    ->color(fn ($record) => $record->expired_at < now() ? 'danger' : 'warning')
                    ->weight('bold'),

                // Update syntax ke TextColumn Badge (Standard V3)
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Non-Aktif')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('chat_wa')
                    ->label('Chat Dosen')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function (Classroom $record) {
                        $phone = $record->teacher->phone ?? '';
                        $text = "Halo {$record->teacher->name}, masa aktif Kelas {$record->name} akan segera berakhir pada {$record->expired_at->format('d M Y')}. Mohon lakukan perpanjangan.";
                        return "https://wa.me/{$phone}?text=" . urlencode($text);
                    })
                    ->openUrlInNewTab(),
            ]);
    }
}
