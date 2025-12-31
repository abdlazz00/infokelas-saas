<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class LatestTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Verifikasi Pembayaran';
//    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Auth::user()->role === 'super_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    // OPTIMASI: Eager Load relasi agar tidak N+1 Query
                    ->with(['user', 'classroom', 'package'])
                    ->where('status', 'pending')
                    ->oldest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket'),

                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Total Transfer')
                    ->money('IDR')
                    ->weight('bold'),

                Tables\Columns\ImageColumn::make('proof_of_payment')
                    ->label('Bukti Bayar')
                    ->disk('public')
                    ->visibility('public')
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve_payment')
                    ->label('Verifikasi') // Disingkat biar muat
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->modalDescription('Apakah bukti bayar valid? Status akan menjadi PAID dan masa aktif kelas diperpanjang.')
                    ->modalSubmitActionLabel('Ya, Valid')
                    ->action(function (Transaction $record) {
                        DB::transaction(function () use ($record) {
                            // 1. Update Transaksi
                            $record->update([
                                'status' => 'approved',
                                'updated_at' => now(),
                            ]);

                            // 2. Logic Perpanjangan
                            $classroom = $record->classroom;
                            $duration = $record->package->duration_days ?? 30;

                            $currentExpired = $classroom->expired_at;

                            // Jika masih aktif, tambah hari dari sisa expired
                            // Jika sudah mati/baru, hitung dari hari ini
                            if ($currentExpired && $currentExpired > now()) {
                                $newExpired = $currentExpired->copy()->addDays($duration);
                            } else {
                                $newExpired = now()->addDays($duration);
                            }

                            $classroom->update([
                                'expired_at' => $newExpired,
                                'is_active' => true,
                            ]);
                        });

                        Notification::make()
                            ->title('Pembayaran Berhasil Diverifikasi')
                            ->body("Kelas aktif hingga " . $record->classroom->expired_at->format('d M Y'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject_payment')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()->title('Transaksi Ditolak')->danger()->send();
                    }),
            ]);
    }
}
