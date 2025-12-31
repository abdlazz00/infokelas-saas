<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Filament\Resources\MaterialResource\RelationManagers;
use App\Models\Classroom;
use App\Models\Material;
use App\Models\Subject;
use App\Models\WaGroup; // <--- Import Model WaGroup
use App\Jobs\SendWhatsappMaterial; // <--- Import Job
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
use Filament\Notifications\Notification; // <--- Import Notification

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'lucide-book-text';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?int $navigationSort = 13;

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
            ->columns(3) // 1. Wajib set 3 kolom di sini agar layout terbagi
            ->schema([

                // === KOLOM KIRI (SPAN 2): ISIAN UTAMA ===
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\Section::make('Informasi Materi')
                            ->description('Upload dokumen atau link referensi pembelajaran.')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Judul Materi')
                                    ->placeholder('Contoh: Pengantar Algoritma & Pemrograman')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi / Catatan')
                                    ->placeholder('Tuliskan ringkasan materi atau instruksi bacaan...')
                                    ->rows(5)
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('file_path')
                                    ->label('Upload File (PDF/PPT/Word)')
                                    ->directory('materials')
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])
                                    ->maxSize(20480) // 20MB
                                    ->downloadable()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('link_url')
                                    ->label('Link Eksternal (Opsional)')
                                    ->placeholder('https://youtube.com/...')
                                    ->url()
                                    ->prefix('https://')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // === KOLOM KANAN (SPAN 1): SETTINGS ===
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Forms\Components\Section::make('Konfigurasi')
                            ->description('Atur target kelas dan notifikasi.')
                            ->schema([

                                // 1. KELAS
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

                                // 2. MATA KULIAH
                                Forms\Components\Select::make('subject_id')
                                    ->label('Mata Kuliah')
                                    ->options(fn (Get $get) => Subject::query()
                                        ->where('classroom_id', $get('classroom_id'))
                                        ->where('is_active', true)
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                // 3. WA GROUP
                                Forms\Components\Select::make('wa_group_id')
                                    ->label('Notifikasi WA')
                                    ->placeholder('Pilih Group WA')
                                    ->options(function (Get $get) {
                                        $classroomId = $get('classroom_id');
                                        if (!$classroomId) return [];

                                        $classroom = Classroom::find($classroomId);
                                        if ($classroom) {
                                            return WaGroup::where('user_id', $classroom->teacher_id)
                                                ->pluck('name', 'jid');
                                        }
                                        return [];
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Kirim notifikasi otomatis ke group.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classroom.name')->badge(),
                Tables\Columns\TextColumn::make('subject.name')->label('Matkul'),
                Tables\Columns\TextColumn::make('title')->weight('bold')->searchable()->limit(30),

                Tables\Columns\IconColumn::make('file_path')
                    ->label('File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon(''),

                Tables\Columns\IconColumn::make('link_url')
                    ->label('Link')
                    ->boolean()
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon(''),

                Tables\Columns\TextColumn::make('created_at')->date('d M Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('')
                    ->tooltip('Download File')
                    ->url(fn ($record) => $record->file_path ? asset('storage/' . $record->file_path) : null)
                    ->visible(fn ($record) => $record->file_path)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('resendWa')
                    ->icon('heroicon-o-paper-airplane') // Icon Pesawat
                    ->color('success') // Warna Hijau
                    ->label('')
                    ->tooltip('Broadcast Ulang ke WA') // Muncul saat di-hover

                    // --- KONFIGURASI MODAL POPUP ---
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang Notifikasi?')
                    ->modalDescription('Pesan WhatsApp berisi link materi ini akan dikirim ulang ke Group yang terdaftar. Lanjutkan?')
                    ->modalSubmitActionLabel('Ya, Kirim') // Ganti tulisan "Confirm" jadi ini
                    ->modalIcon('heroicon-o-chat-bubble-left-ellipsis') // Icon di dalam popup
                    // -------------------------------

                    ->action(function (Material $record) {
                        if($record->wa_group_id) {
                            // Panggil Job Queue
                            SendWhatsappMaterial::dispatch($record);

                            Notification::make()
                                ->title('Broadcast Diproses')
                                ->body('Pesan sedang dikirim di latar belakang.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Materi ini belum di-set ke Group WA manapun.')
                                ->warning()
                                ->send();
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
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
