<?php
namespace App\Services;

use App\Models\Package;
use App\Models\Transaction;
use App\Models\Classroom;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionService
{
    public function calculatePricing(?string $packageId, ?string $voucherCode): array
    {
        $subtotal = 0;
        $discount = 0;

        if ($packageId) {
            $package = Package::find($packageId);
            $subtotal = $package?->price ?? 0;
        }

        if ($voucherCode) {
            $voucher = Voucher::where('code', $voucherCode)->first();

            if ($voucher && $voucher->is_active) {
                if ($voucher->type === 'percent') {
                    $discount = $subtotal * ($voucher->amount / 100);
                } else {
                    $discount = $voucher->amount;
                }
            }
        }

        $finalAmount = max(0, $subtotal - $discount);
        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
        ];
    }



    //Approve Transaksi dan perpanjang masa aktif kelas
    public function approve(Transaction $transaction): Transaction
    {
        if ($transaction->status !== 'pending') {
            throw new Exception("Transaksi ini sudah diproses (Status: {$transaction->status}).");
        }

        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => 'approved',
                'admin_note' => 'Verified by System/Admin',
            ]);

            // 3. Ambil Data Kelas & Paket
            $classroom = $transaction->classroom;
            $package = $transaction->package;

            if (!$classroom || !$package) {
                throw new Exception("Data kelas atau paket tidak valid.");
            }

            // 4. Hitung Tanggal Expired Baru
            $duration = $package->duration_days;
            $currentExpired = $classroom->expired_at;

            if ($currentExpired && $currentExpired > now()) {
                $newExpired = $currentExpired->copy()->addDays($duration);
            } else {
                $newExpired = now()->addDays($duration);
            }

            $classroom->update([
                'subscription_status' => 'active',
                'is_active' => true,
                'expired_at' => $newExpired,
            ]);

            return $transaction;
        });
    }

    //Reject Transakti
    public function reject(Transaction $transaction, string $reason = null): Transaction
    {
        if ($transaction->status !== 'pending') {
            throw new Exception("Transaksi tidak bisa ditolak karena status bukan pending.");
        }
        $transaction->update([
            'status' => 'rejected',
            'admin_note' =>$reason ?? 'Ditolak oleh System.',
        ]);
        return $transaction;
    }
}
