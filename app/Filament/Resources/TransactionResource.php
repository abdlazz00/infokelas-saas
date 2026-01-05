<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Classroom;
use App\Services\TransactionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString; // Import HtmlString for Popup
use Illuminate\Support\Facades\Storage; // Import Storage

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    // === 1. FORM TRANSAKSI ===
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\Section::make('Informasi Langganan')
                            ->schema([
                                // 1. Admin Kelas (User)
                                Forms\Components\Select::make('user_id')
                                    ->label('Admin Kelas')
                                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'admin_kelas'))
                                    ->default(auth()->id())
                                    ->disabled(fn () => auth()->user()->role === 'admin_kelas')
                                    ->dehydrated()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('classroom_id', null)),

                                // 2. Kelas (Filter sesuai Admin Kelas)
                                Forms\Components\Select::make('classroom_id')
                                    ->label('Kelas')
                                    ->options(function (Get $get) {
                                        $userId = $get('user_id');
                                        if (! $userId) return [];

                                        return Classroom::where('teacher_id', $userId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                // 3. Paket
                                Forms\Components\Select::make('package_id')
                                    ->label('Paket')
                                    ->relationship('package', 'name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateTotals($get, $set);
                                    }),

                                // 4. Voucher
                                Forms\Components\TextInput::make('voucher_code')
                                    ->label('Kode Voucher (Opsional)')
                                    ->placeholder('Masukan kode promo...')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateTotals($get, $set);
                                    }),
                            ])->columns(2),

                        Forms\Components\Section::make('Rincian Pembayaran')
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Diskon')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->default(0),

                                Forms\Components\TextInput::make('final_amount')
                                    ->label('Total Bayar')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),
                            ])->columns(3),
                    ]),

                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Forms\Components\Section::make('Status & Bukti')
                            ->schema([
                                // 5. Status
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->disabled(fn () => auth()->user()->role === 'admin_kelas')
                                    ->dehydrated()
                                    ->required()
                                    ->native(false),

                                Forms\Components\FileUpload::make('proof_of_payment')
                                    ->label('Bukti Transfer')
                                    ->image()
                                    ->directory('payment_proofs')
                                    ->openable()
                                    ->downloadable(),

                                Forms\Components\Textarea::make('admin_note')
                                    ->label('Catatan Admin')
                                    ->rows(3)
                                    ->visible(fn () => auth()->user()->role === 'super_admin'),
                            ]),
                    ]),
            ])->columns(3);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $service = new TransactionService();

        $pricing = $service->calculatePricing(
            $get('package_id'),
            $get('voucher_code')
        );

        $set('subtotal', $pricing['subtotal']);
        $set('discount_amount', $pricing['discount_amount']);
        $set('final_amount', $pricing['final_amount']);
    }

    // === 2. TABLE TRANSAKSI ===
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin Kelas')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket')
                    ->badge()
                    ->color('primary'),

                // KOLOM BUKTI PEMBAYARAN (KLIK UNTUK POPUP)
                Tables\Columns\ImageColumn::make('proof_of_payment')
                    ->label('Bukti')
                    ->visibility('public')
                    ->action(
                        Tables\Actions\Action::make('view_proof')
                            ->modalHeading('Bukti Pembayaran')
                            ->modalContent(fn (Transaction $record) => new HtmlString(
                                '<div class="flex justify-center">
                                    <img src="'. Storage::url($record->proof_of_payment) .'" alt="Bukti Transfer" style="max-width: 100%; max-height: 80vh; border-radius: 8px;">
                                </div>'
                            ))
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->closeModalByClickingAway(true)
                    ),

                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // BUTTON EDIT DIHAPUS (Sesuai Request)

                // APPROVE
                Tables\Actions\Action::make('approve_payment')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->modalDescription('Pastikan bukti transfer valid. Masa aktif kelas akan diperpanjang otomatis.')
                    ->action(function (Transaction $record, TransactionService $service) {
                        try {
                            $service->approve($record);
                            Notification::make()->title('Transaksi Berhasil')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn (Transaction $record) =>
                        $record->status === 'pending' && auth()->user()->role === 'super_admin'
                    ),

                // REJECT
                Tables\Actions\Action::make('reject_payment')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Penolakan')
                            ->required()
                    ])
                    ->action(function (Transaction $record, array $data, TransactionService $service) {
                        try {
                            $service->reject($record, $data['reason']);
                            Notification::make()->title('Transaksi Ditolak')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn (Transaction $record) =>
                        $record->status === 'pending' && auth()->user()->role === 'super_admin'
                    ),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'classroom', 'package']);
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->role !== 'super_admin') {
            return null;
        }

        return Cache::remember('nav_badge_transactions', 120, function () {
            $count = static::getModel()::where('status', 'pending')->count();
            return $count > 0 ? (string) $count : null;
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
