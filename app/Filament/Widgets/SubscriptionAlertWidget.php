<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Classroom;

class SubscriptionAlertWidget extends Widget
{
    protected static string $view = 'filament.widgets.subscription-alert-widget';

    // Tampil paling atas (bahkan di atas statistik) agar terlihat urgensinya
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full'; // Lebar penuh

    public static function canView(): bool
    {
        // 1. Super Admin tidak perlu lihat ini
        if (Auth::user()->role === 'super_admin') return false;

        // 2. Hanya tampil JIKA ada kelas yang expired <= 7 hari lagi
        return Classroom::where('teacher_id', Auth::id())
            ->where('is_active', true)
            ->whereDate('expired_at', '<=', now()->addDays(7))
            ->exists();
    }

    // Kirim data kelas yang bermasalah ke View
    protected function getViewData(): array
    {
        return [
            'expiringClasses' => Classroom::where('teacher_id', Auth::id())
                ->where('is_active', true)
                ->whereDate('expired_at', '<=', now()->addDays(7))
                ->get()
        ];
    }
}
