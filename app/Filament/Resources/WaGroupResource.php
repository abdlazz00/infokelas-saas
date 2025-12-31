<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaGroupResource\Pages;
use App\Filament\Resources\WaGroupResource\RelationManagers;
use App\Models\WaGroup;
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

class WaGroupResource extends Resource
{
    protected static ?string $model = WaGroup::class;

    protected static ?string $navigationIcon = 'lucide-book-user';
    protected static ?string $navigationGroup = 'Academic';
    protected static ?int $navigationSort =16;
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
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Group WhatsApp')
                        ->placeholder('Contoh: Grup Diskusi Kelas A')
                        ->required(),

                    // LOGIC SPESIAL: JID
                    // Hanya Super Admin yang bisa edit. Dosen cuma bisa lihat (disabled).
                    Forms\Components\TextInput::make('jid')
                        ->label('Group ID (Fonnte)')
                        ->placeholder(fn() => Auth::user()->role === 'super_admin' ? 'Input ID dari Fonnte' : 'Menunggu Admin...')
                        ->disabled(fn() => Auth::user()->role !== 'super_admin') // Dosen gak bisa isi
                        ->helperText(fn() => Auth::user()->role !== 'super_admin'
                            ? 'Silakan invite Bot ke grup WA Anda, lalu hubungi Admin untuk aktivasi.'
                            : 'Ambil ID dari fitur Get Groups di Fonnte.'),
                ])->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classroom.name')->badge(),
                Tables\Columns\TextColumn::make('name')->label('Nama Group')->searchable(),

                Tables\Columns\TextColumn::make('jid')
                    ->label('Status Koneksi')
                    ->formatStateUsing(fn ($state) => $state ? 'TERHUBUNG' : 'BELUM KONEK')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListWaGroups::route('/'),
            'create' => Pages\CreateWaGroup::route('/create'),
            'edit' => Pages\EditWaGroup::route('/{record}/edit'),
        ];
    }
}
