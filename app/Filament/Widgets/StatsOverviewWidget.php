<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Classroom;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return Auth::user()->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $totalPemasukan = Cache::remember('stats_revenue', 300, function () {
            return Transaction::where('status', 'approved')->sum('final_amount');
        });
        $totalKelas = Cache::remember('stats_active_classes', 300, function () {
            return Classroom::where('is_active', true)->count();
        });
        $totalMahasiswa = Cache::remember('stats_students', 300, function () {
            return User::where('role', 'mahasiswa')->count();
        });

        return [
            Stat::make('Total Pemasukan', 'Rp ' . number_format($totalPemasukan, 0, ',', '.'))
                ->description('Semua transaksi lunas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 3, 10, 5, 15, 4, 17]),

            Stat::make('Kelas Aktif', $totalKelas)
                ->description('Kelas berjalan saat ini')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Total Mahasiswa', $totalMahasiswa)
                ->description('User terdaftar sebagai mahasiswa')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
