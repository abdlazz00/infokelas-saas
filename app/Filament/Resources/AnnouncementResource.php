<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Jobs\SendWhatsappAnnouncement;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\WaGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\Section::make('Informasi Pengumuman')
                            ->description('Isi detail berita atau informasi yang ingin disampaikan.')
                            ->schema([
                                Forms\Components\Hidden::make('user_id')
                                    ->default(Auth::id()),

                                Forms\Components\TextInput::make('title')
                                    ->label('Judul Pengumuman')
                                    ->placeholder('Contoh: Perubahan Jadwal UAS')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('content')
                                    ->label('Isi Pesan')
                                    ->placeholder('Tulis detail pengumuman di sini...')
                                    ->rows(10)
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('image')
                                    ->label('Lampiran Gambar (Opsional)')
                                    ->image()
                                    ->directory('announcements')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR SETTINGS) - Span 1 ===
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Forms\Components\Section::make('Target & Publikasi')
                            ->description('Atur tujuan dan status pengumuman.')
                            ->schema([
                                Forms\Components\Select::make('classroom_id')
                                    ->label('Tujukan untuk Kelas')
                                    ->relationship(
                                        name: 'classroom',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => Auth::user()->role === 'super_admin'
                                            ? $query
                                            : $query->where('teacher_id', Auth::id())
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live() // Update realtime
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $set('wa_group_id', null);

                                        if (!$state) return;

                                        $classroom = \App\Models\Classroom::find($state);
                                        if ($classroom && $classroom->subscription_status !== 'active') {
                                            Notification::make()
                                                ->title('Akses Ditolak')
                                                ->body('Kelas ini sedang tidak aktif (Expired).')
                                                ->danger()
                                                ->send();

                                            $set('classroom_id', null);
                                        }
                                    })
                                    ->rules([
                                        fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                            $classroom = \App\Models\Classroom::find($value);
                                            if ($classroom && $classroom->subscription_status !== 'active') {
                                                $fail('Kelas ini sedang tidak aktif.');
                                            }
                                        },
                                    ]),

                                Forms\Components\Select::make('wa_group_id')
                                    ->label('Broadcast WhatsApp')
                                    ->placeholder('Pilih Group WA')
                                    ->options(function (Forms\Get $get) {
                                        $classroomId = $get('classroom_id');
                                        if (!$classroomId) return [];

                                        return \App\Models\WaGroup::where('classroom_id', $classroomId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Opsi muncul setelah kelas dipilih.'),

                                Forms\Components\Select::make('type')
                                    ->label('Tipe Pesan')
                                    ->options([
                                        'info' => 'Informasi (Biru)',
                                        'warning' => 'Penting (Kuning)',
                                        'danger' => 'Darurat (Merah)',
                                    ])
                                    ->default('info')
                                    ->required()
                                    ->native(false),

                                // 4. STATUS
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Terbitkan Langsung?')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'info',
                        'warning' => 'warning',
                        'danger' => 'danger',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'info' => 'Info',
                        'warning' => 'Penting',
                        'danger' => 'Darurat',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('waGroup.name')
                ->label('Group WA')
                    ->badge()
                    ->color('success')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Publikasi'),
            ])
            ->actions([
                Tables\Actions\Action::make('resendWa')
                    ->label('')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->button()
                    ->tooltip('Broadcast Pengumuman ke WA')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang Pengumuman?')
                    ->modalDescription('Pengumuman ini akan dikirim ulang ke Group WA yang terdaftar. Lanjutkan?')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function (Announcement $record) {
                        SendWhatsappAnnouncement::dispatch($record);

                        Notification::make()
                            ->title('Broadcast Diproses')
                            ->body('Pengumuman sedang dikirim.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Announcement $record) => !empty($record->wa_group_id)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->role !== 'super_admin') {
            $query->where('user_id', $user->id);
        }

        return $query->latest();
    }
}
