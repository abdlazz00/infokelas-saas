<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassroomResource\Pages;
use App\Filament\Resources\ClassroomResource\RelationManagers;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static ?string $navigationIcon = 'lucide-school';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?int $navigationSort = 15;
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()->role === 'admin_kelas') {
            $query->where('teacher_id', Auth::id());
        }
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                // === KOLOM KIRI: Identitas Kelas ===
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\Section::make('Identitas Kelas')
                            ->schema([
                                Forms\Components\Select::make('teacher_id')
                                    ->label('Admin Kelas')
                                    ->relationship('teacher', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn () => auth()->user()->role === 'admin_kelas' ? auth()->id() : null)
                                    ->disabled(fn () => auth()->user()->role !== 'super_admin')
                                    ->dehydrated()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kelas')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('university')
                                            ->label('Universitas')
                                            ->required(),

                                        Forms\Components\TextInput::make('major')
                                            ->label('Jurusan')
                                            ->required(),

                                        Forms\Components\TextInput::make('semester')
                                            ->label('Semester')
                                            ->numeric()
                                            ->required(),
                                    ]),
                            ]),
                    ]),

                // === KOLOM KANAN: Monitoring ===
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Kode Join
                        Forms\Components\Section::make('Kode Join')
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Kode Unik')
                                    ->default(fn () => strtoupper(\Illuminate\Support\Str::random(6)))
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'text-center text-xl font-bold tracking-widest text-primary-600'])
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('copy')
                                            ->icon('heroicon-m-clipboard-document-check')
                                            ->action(fn ($state) => $state)
                                            ->successNotificationTitle('Kode disalin!')
                                    ),
                            ]),

                        // Status Langganan
                        Forms\Components\Section::make('Status Langganan')
                            ->schema([
                                Forms\Components\Select::make('subscription_status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'expired' => 'Expired',
                                        'inactive' => 'Inactive (Belum Bayar)',
                                    ])
                                    ->default('inactive')
                                    ->disabled() // Dikunci
                                    ->dehydrated(), // Tetap disimpan

                                Forms\Components\DateTimePicker::make('expired_at')
                                    ->label('Berlaku Hingga')
                                    ->readOnly(),

                                // FIX: Menghapus ->color('gray') agar tidak error
                                Forms\Components\Placeholder::make('sisa_hari')
                                    ->label('Sisa Waktu')
                                    ->content(fn ($record) => $record && $record->expired_at && $record->expired_at > now()
                                        ? $record->expired_at->diffForHumans()
                                        : '-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kelas')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Dosen')
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Join')
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('subscription_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'inactive' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('expired_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
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
            RelationManagers\StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassrooms::route('/'),
            'create' => Pages\CreateClassroom::route('/create'),
            'edit' => Pages\EditClassroom::route('/{record}/edit'),
        ];
    }
}
