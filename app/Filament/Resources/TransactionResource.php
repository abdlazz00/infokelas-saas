<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Classroom;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\Voucher;
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

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'lucide-circle-dollar-sign';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\Section::make('Informasi Langganan')
                            ->description('Detail kelas dan paket yang dipilih.')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Admin Kelas')
                                    ->relationship('user', 'name', fn (Builder $query) => $query->where('role', 'admin_kelas'))
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->user()->role === 'admin_kelas' ? auth()->id() : null)
//                                    ->hidden(fn () => auth()->user()->role !== 'super_admin')
                                    ->dehydrated()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('classroom_id', null))
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('classroom_id')
                                    ->label('Kelas')
                                    ->options(function (Get $get) {
                                        $teacherId = $get('user_id');
                                        if (!$teacherId && auth()->user()->role === 'admin_kelas') {
                                            $teacherId = auth()->id();
                                        }
                                        if (!$teacherId) return [];

                                        return Classroom::where('teacher_id', $teacherId)
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->live(),

                                Forms\Components\Select::make('package_id')
                                    ->label('Paket')
                                    ->relationship('package', 'name', fn (Builder $query) => $query->where('is_active', true))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $package = \App\Models\Package::find($state);
                                        if ($package) {
                                            $set('subtotal', $package->price);
                                            $set('final_amount', $package->price);
                                            $set('voucher_code', null);
                                            $set('discount_amount', 0);
                                        }
                                    }),
                            ])->columns(2),

                        Forms\Components\Section::make('Bukti Pembayaran')
                            ->description('Lampiran bukti transfer.')
                            ->schema([
                                Forms\Components\FileUpload::make('proof_of_payment')
                                    ->label('')
                                    ->disk('public')
                                    ->directory('payments')
                                    ->visibility('public')
                                    ->image()
                                    ->maxSize(5120)
                                    ->openable()
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Forms\Components\Section::make('Rincian Biaya')
                            ->schema([
                                Forms\Components\TextInput::make('voucher_code')
                                    ->label('Kode Voucher')
                                    ->placeholder('Masukan Kode')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        if (!$state) {
                                            $set('discount_amount', 0);
                                            $set('final_amount', $get('subtotal'));
                                            return;
                                        }
                                        $voucher = Voucher::where('code', $state)->first();
                                        if (!$voucher) {
                                            Notification::make()->title('Kode Voucher Tidak Ditemukan')->danger()->send();
                                            $set('voucher_code', null); return;
                                        }
                                        if ($voucher->expired_at < now()) {
                                            Notification::make()->title('Voucher Expired')->danger()->send();
                                            $set('voucher_code', null); return;
                                        }
                                        $buyerId = $get('user_id') ?? auth()->id();
                                        $usageCount = \App\Models\Transaction::where('user_id', $buyerId)
                                            ->where('voucher_code', $state)
                                            ->where('status', 'approved')->count();
                                        if ($usageCount >= $voucher->limit_per_user) {
                                            Notification::make()->title('Limit voucher tercapai')->warning()->send();
                                            $set('voucher_code', null); return;
                                        }

                                        $subtotal = (int) $get('subtotal');
                                        $discount = $voucher->type === 'fixed' ? $voucher->amount : ($subtotal * $voucher->amount) / 100;
                                        $set('discount_amount', $discount);
                                        $set('final_amount', max(0, $subtotal - $discount));
                                        Notification::make()->title("Hemat Rp " . number_format($discount))->success()->send();
                                    }),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Harga Paket')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly(),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Diskon')
                                    ->numeric()
                                    ->prefix('- Rp')
                                    ->readOnly()
                                    ->default(0),

                                Forms\Components\TextInput::make('final_amount')
                                    ->label('Total Bayar')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'text-xl font-bold text-primary-600'])
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status Transaksi')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->dehydrated()
                                    ->selectablePlaceholder(false),
                            ])
                            ->hidden(fn () => auth()->user()->role !== 'super_admin'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin Kelas')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\ImageColumn::make('proof_of_payment')
                    ->label('Bukti Transfer')
                    ->disk('public')
                    ->circular(false)
                    ->height(80)
                    ->url(fn (Transaction $record) => $record->proof_of_payment ? asset('storage/' . $record->proof_of_payment) : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket'),

                Tables\Columns\TextColumn::make('final_amount')
                    ->money('IDR')
                    ->label('Total')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve_payment')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button() // Opsional: Tampil sebagai tombol
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->modalDescription('Pastikan bukti transfer valid.')

                    // LOGIC BARU: Hanya muncul jika PENDING + User adalah SUPER ADMIN
                    ->visible(fn (Transaction $record) =>
                        $record->status === 'pending' &&
                        auth()->user()->role === 'super_admin'
                    )

                    ->action(function (Transaction $record) {
                        // ... (Logic action tetap sama, tidak perlu diubah) ...
                        $record->update(['status' => 'approved']);
                        $package = $record->package;
                        $classroom = $record->classroom;

                        if ($classroom && $package) {
                            $currentExpired = $classroom->expired_at && $classroom->expired_at > now()
                                ? $classroom->expired_at
                                : now();
                            $newExpired = $currentExpired->addDays($package->duration_days);

                            $classroom->update([
                                'subscription_status' => 'active',
                                'expired_at' => $newExpired,
                                'is_active' => true, // Pastikan is_active ikut ke-update
                            ]);

                            Notification::make()
                                ->title('Transaksi Berhasil Approved')
                                ->body("Kelas aktif hingga {$newExpired->format('d M Y')}")
                                ->success()
                                ->send();
                        }
                    }),

                // --- TAMBAHKAN INI (ACTION REJECT) ---
                // Biar Super Admin bisa tolak kalau bukti palsu
                Tables\Actions\Action::make('reject_payment')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Transaction $record) =>
                        $record->status === 'pending' &&
                        auth()->user()->role === 'super_admin'
                    )
                    ->action(fn (Transaction $record) => $record->update(['status' => 'rejected'])),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
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

        $count = Cache::remember('nav_badge_transactions', 120, function () {
            return static::getModel()::where('status', 'pending')->count();
        });

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
