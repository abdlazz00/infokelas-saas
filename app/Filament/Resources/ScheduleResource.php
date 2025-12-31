<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Filament\Resources\ScheduleResource\RelationManagers;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'lucide-calendar-check';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?int $navigationSort = 11;

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
            ->schema([
                Forms\Components\Section::make('Detail Jadwal')
                    ->description('Atur jadwal pertemuan mata kuliah.')
                    ->schema([
                        // === 1. PILIH KELAS (DENGAN VALIDASI) ===
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

                            // LOGIC PENGAMANAN
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Reset Matkul saat kelas berubah
                                $set('subject_id', null);

                                if (!$state) return;

                                // Cek Status Kelas
                                $classroom = \App\Models\Classroom::find($state);
                                if ($classroom && $classroom->subscription_status !== 'active') {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Akses Ditolak')
                                        ->body('Kelas ini sedang tidak aktif (Expired). Silakan perpanjang langganan.')
                                        ->danger()
                                        ->send();

                                    $set('classroom_id', null); // Tendang user
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

                        // === 2. PILIH MATKUL (Filter by Class) ===
                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Kuliah')
                            ->options(fn (Get $get) => Subject::query()
                                ->where('classroom_id', $get('classroom_id'))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        // === 3. HARI & RUANGAN ===
                        Forms\Components\Select::make('day_of_week')
                            ->label('Hari')
                            ->options([
                                1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
                                4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('room')
                            ->label('Ruangan / Link Zoom')
                            ->placeholder('Contoh: R.304 atau Link Zoom')
                            ->required(),

                        // === 4. WAKTU MULAI & SELESAI ===
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Jam Selesai')
                            ->seconds(false)
                            ->required(),

                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classroom.name')->badge(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
                        4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
                    })->sortable(),

                Tables\Columns\TextColumn::make('start_time')->time('H:i'),

                Tables\Columns\TextColumn::make('subject.name')
                ->label('Matkul')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('subject.lecturer')
                ->label('Dosen'),
            ])
            ->defaultSort('day_of_week')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
