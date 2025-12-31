<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Filament\Resources\BankAccountResource\RelationManagers;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'lucide-landmark';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('bank_name')
                        ->label('Nama Bank')
                        ->options([
                            'BCA' => 'BCA',
                            'MANDIRI' => 'Bank Mandiri',
                            'BRI' => 'BRI',
                            'BNI' => 'BNI',
                            'JAGO' => 'Bank Jago',
                            'SEABANK' => 'SeaBank',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('account_number')
                        ->label('Nomor Rekening')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('account_name')
                        ->label('Atas Nama')
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')->badge()->color('info'),
                Tables\Columns\TextColumn::make('account_number')->copyable(),
                Tables\Columns\TextColumn::make('account_name')->weight('bold'),
                Tables\Columns\ToggleColumn::make('is_active'),
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
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
