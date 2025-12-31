<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Filament\Resources\VoucherResource\RelationManagers;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'lucide-receipt-text';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Kode Voucher')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('Contoh: MERDEKA45')
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),

                    Forms\Components\Select::make('type')
                        ->options([
                            'fixed' => 'Potongan Nominal (Rp)',
                            'percent' => 'Potongan Persen (%)',
                        ])
                        ->required()
                        ->default('fixed'),

                    Forms\Components\TextInput::make('amount')
                        ->label('Besar Potongan')
                        ->numeric()
                        ->required()
                        ->prefix('Rp / %'),

                    Forms\Components\DateTimePicker::make('expired_at')
                        ->label('Berlaku Sampai')
                        ->required(),

                    Forms\Components\TextInput::make('limit_per_user')
                        ->label('Limit Per User')
                        ->numeric()
                        ->default(1)
                        ->helperText('Berapa kali 1 user bisa pakai kode ini?'),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Voucher')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'info',
                        'percent' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fixed' => 'Nominal (Rp)',
                        'percent' => 'Persen (%)',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Besar Potongan')
                    ->formatStateUsing(fn (Voucher $record): string => match ($record->type) {
                        'fixed' => 'Rp ' . number_format($record->amount, 0, ',', '.'),
                        'percent' => (int) $record->amount . '%',
                        default => $record->amount,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Berlaku Sampai')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('limit_per_user')
                    ->label('Limit/User')
                    ->alignCenter(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }
}
