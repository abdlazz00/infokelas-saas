<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Services\TransactionService; // <--- Import Service
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Forms;

class LatestTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Verifikasi Pembayaran Terbaru';
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->with(['user', 'classroom', 'package'])
                    ->where('status', 'pending')
                    ->oldest() // Yang paling lama menunggu ditaruh paling atas
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin Kelas')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket'),

                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold'),

                // Tambahkan kolom bukti bayar agar admin bisa cek langsung
                Tables\Columns\ImageColumn::make('proof_of_payment')
                    ->label('Bukti')
                    ->visibility('public')
                    ->openUrlInNewTab(),
            ])
            ->actions([
                // ACTION APPROVE (Pakai Service)
                Tables\Actions\Action::make('approve_payment')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->action(function (Transaction $record, TransactionService $service) {
                        try {
                            // Panggil Service (Otomatis dispatch Job WA)
                            $service->approve($record);

                            Notification::make()->title('Transaksi Berhasil')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // ACTION REJECT (Pakai Service)
                Tables\Actions\Action::make('reject_payment')
                    ->label('Tolak')
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
                    }),
            ]);
    }
}
