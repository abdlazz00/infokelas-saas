<?php

namespace App\Filament\Resources\ClassroomResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'Daftar Mahasiswa';

    public function isReadOnly(): bool
    {
        $classroom = $this->getOwnerRecord();

        return $classroom->subscription_status !== 'active';
    }
    // ========================

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique('users', 'email'),

                Forms\Components\TextInput::make('nim')
                    ->label('NIM')
                    ->unique('users', 'nim')
                    ->required(),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->required()
                    ->visibleOn('create'), // Hanya wajib saat create

                Forms\Components\Hidden::make('role')
                    ->default('mahasiswa'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Mahasiswa')
                    ->icon('heroicon-o-user-circle') // Icon User
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nim')
                    ->label('NIM')
                    ->copyable()
                    ->copyMessage('NIM disalin!')
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->copyable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Aktif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->tooltip('Matikan untuk mengarsipkan mahasiswa (tidak bisa login)'),

                Tables\Columns\TextColumn::make('pivot.joined_at')
                    ->label('Bergabung')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Input Mahasiswa Baru')
                    ->icon('heroicon-o-plus')
                    ->slideOver()
                    ->modalHeading('Registrasi Mahasiswa')
                    ->modalDescription('Mahasiswa akan otomatis masuk ke kelas ini.'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Keluarkan')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Keluarkan Mahasiswa?')
                    ->modalDescription('Mahasiswa tidak akan bisa mengakses materi kelas ini lagi.'),
            ])
            ->emptyStateHeading('Belum ada mahasiswa')
            ->emptyStateDescription('Silakan input mahasiswa baru untuk memulai kelas.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
