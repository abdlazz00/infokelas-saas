<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LatestAssignmentsWidget extends BaseWidget
{
    // Judul Widget
    protected static ?string $heading = 'Daftar Tugas Terbaru';

    // Tampil di urutan ke-2 (Setelah Stats)
    protected static ?int $sort = 2;

    // Ambil lebar penuh
    protected int | string | array $columnSpan = 'full';

    // Widget ini HANYA untuk Dosen (Admin Kelas)
    public static function canView(): bool
    {
        return Auth::user()->role !== 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Assignment::query()
                    ->whereHas('classroom', function ($query) {
                        $query->where('teacher_id', Auth::id());
                    })
                    ->latest('created_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Tugas')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('deadline')
                    ->label('Batas Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->deadline < now() ? 'Ditutup' : 'Dibuka')
                    ->colors([
                        'danger' => 'Ditutup',
                        'success' => 'Dibuka',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Assignment $record) => "/admin/assignments/{$record->id}/edit"),
            ]);
    }
}
