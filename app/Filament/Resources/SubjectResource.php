<?php

namespace App\Filament\Resources;

use App\Models\Classroom;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\SubjectResource\Pages;
use App\Filament\Resources\SubjectResource\RelationManagers;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'lucide-book-copy';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?int $navigationSort = 14;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika Super Admin, tampilkan semua
        if (Auth::user()->role === 'super_admin') {
            return $query;
        }

        // Jika Dosen, tampilkan subject yang ada di kelas miliknya saja
        return $query->whereHas('classroom', function ($q) {
            $q->where('teacher_id', Auth::user()->id);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('classroom_id')
                        ->label('Kelas')
                        ->relationship(
                            name: 'classroom',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => Auth::user()->role === 'super_admin'
                                ? $query
                                : $query->where('teacher_id', Auth::user()->id)
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (!$state) return;

                            $classroom = Classroom::find($state);

                            if ($classroom && $classroom->subscription_status !== 'active') {
                                Notification::make()
                                    ->title('Kelas Tidak Aktif')
                                    ->body('Silakan lakukan perpanjangan langganan.')
                                    ->danger()
                                    ->send();

                                $set('classroom_id', null);
                            }
                        })
                        ->rules([
                            fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                $classroom = Classroom::find($value);
                                if ($classroom && $classroom->subscription_status !== 'active') {
                                    $fail('Kelas ini sedang tidak aktif (Expired).');
                                }
                            },
                        ]),

                    Forms\Components\TextInput::make('code')
                        ->label('Kode Matkul')
                        ->placeholder('Contoh: IF101')
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Mata Kuliah')
                        ->required(),

                    Forms\Components\TextInput::make('sks')
                        ->label('SKS')
                        ->numeric()
                        ->default(2)
                        ->required(),

                    Forms\Components\TextInput::make('semester')
                        ->label('Semester')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required(),

                    Forms\Components\TextInput::make('lecturer')
                        ->label('Dosen Pengampu')
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->default(true)

                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('semester')
                    ->label('Smt')
                    ->alignCenter()
                    ->sortable(),

                // Tampilkan Kode Matkul
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Mata Kuliah')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->lecturer),

                Tables\Columns\TextColumn::make('sks')
                    ->label('SKS')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        1 => 'Aktif (Semester Ini)',
                        0 => 'Arsip (Semester Lalu)',
                    ])
                    ->default(1)
                    ->label('Status'),

                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        1 => 'Semester 1',
                        2 => 'Semester 2',
                        3 => 'Semester 3',
                        4 => 'Semester 4',
                        5 => 'Semester 5',
                        6 => 'Semester 6',
                        7 => 'Semester 7',
                        8 => 'Semester 8',
                    ])
                    ->label('Filter Semester'),
            ])
            ->defaultSort('semester', 'desc')
            ->actions([
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
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
