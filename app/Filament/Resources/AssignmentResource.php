<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Filament\Resources\AssignmentResource\RelationManagers;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\WaGroup; // <--- Import Model WaGroup
use App\Jobs\SendWhatsappAssignment; // <--- Import Job Broadcast
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?int $navigationSort = 12;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()->role === 'super_admin') {
            return $query;
        }

        return $query->whereHas('classroom', function ($q) {
            $q->where('teacher_id', Auth::user()->id);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3) // 1. Setup Grid 3 Kolom
            ->schema([

                // === KOLOM KIRI: KONTEN TUGAS (Span 2) ===
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\Section::make('Informasi Tugas')
                            ->description('Isi detail tugas dan lampiran soal.')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Judul Tugas')
                                    ->placeholder('Contoh: Analisis Jurnal Sistem Informasi')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Instruksi / Soal')
                                    ->placeholder('Tuliskan instruksi pengerjaan tugas secara detail di sini...')
                                    ->rows(10) // Lebih tinggi agar nyaman nulis panjang
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('file_url')
                                    ->label('Lampiran File (Opsional)')
                                    ->helperText('Format: PDF, Word, atau Gambar (Max 10MB)')
                                    ->directory('assignments')
                                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->maxSize(10240)
                                    ->downloadable()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // === KOLOM KANAN: TARGET & SETTINGS (Span 1) ===
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Forms\Components\Section::make('Konfigurasi')
                            ->description('Atur target kelas dan tenggat waktu.')
                            ->schema([
                                Forms\Components\Select::make('classroom_id')
                                    ->label('Kelas')
                                    ->relationship(
                                        name: 'classroom',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => Auth::user()->role === 'super_admin'
                                            ? $query
                                            : $query->where('teacher_id', Auth::user()->id)
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('subject_id', null);
                                        $set('wa_group_id', null);
                                        if (!$state) return;
                                        $classroom = Classroom::find($state);
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
                                            $classroom = Classroom::find($value);
                                            if ($classroom && $classroom->subscription_status !== 'active') {
                                                $fail('Kelas ini sedang tidak aktif.');
                                            }
                                        },
                                    ]),

                                Forms\Components\Select::make('subject_id')
                                    ->label('Mata Kuliah')
                                    ->options(fn (Get $get) => Subject::query()
                                        ->where('classroom_id', $get('classroom_id'))
                                        ->where('is_active', true)
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\DateTimePicker::make('deadline')
                                    ->label('Batas Waktu')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false),

                                Forms\Components\Select::make('wa_group_id')
                                    ->label('Notifikasi WA')
                                    ->placeholder('Pilih Group WA')
                                    ->options(function (Get $get) {
                                        $classroomId = $get('classroom_id');
                                        if (!$classroomId) return [];

                                        return WaGroup::where('classroom_id', $classroomId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Kirim notifikasi otomatis ke group.'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Tampilkan Tugas?')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classroom.name')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Matkul')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->weight('bold')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('deadline')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success'),

                // KOLOM DOWNLOAD LAMPIRAN (FITUR BARU)
                Tables\Columns\TextColumn::make('file_url')
                    ->label('Lampiran')
                    ->formatStateUsing(fn ($state) => $state ? 'Download' : '-')
                    ->url(fn ($record) => $record->file_url ? asset('storage/' . $record->file_url) : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon(fn ($state) => $state ? 'heroicon-o-arrow-down-tray' : null),

                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('resendWa')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->label('')
                    ->button()
                    ->tooltip('Broadcast tugas ke WA')
                    ->requiresConfirmation()
                    ->modalHeading('Broadcast Ulang Tugas?')
                    ->modalDescription('Pesan akan dikirim ke Group WA yang terdaftar di tugas ini.')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function (Assignment $record) {
                        if($record->wa_group_id) {
                            SendWhatsappAssignment::dispatch($record);
                            Notification::make()->title('Broadcast diproses!')->success()->send();
                        } else {
                            Notification::make()->title('Tidak ada Group WA dipilih')->warning()->send();
                        }
                    }),

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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}
