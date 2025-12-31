<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use App\Models\Material;
use App\Models\Classroom;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()->role !== 'super_admin';
    }

    protected function getStats(): array
    {
        $userId = Auth::id();

        $totalStudents = DB::table('class_students')
            ->whereIn('classroom_id', function ($query) use ($userId) {
                $query->select('id')->from('classrooms')->where('teacher_id', $userId);
            })
            ->count();

        $activeAssignments = Assignment::query()
            ->whereHas('classroom', fn($q) => $q->where('teacher_id', $userId))
            ->where('deadline', '>', now())
            ->count();

        // 3. Hitung Total Materi Diupload
        $totalMaterials = Material::query()
            ->whereHas('classroom', fn($q) => $q->where('teacher_id', $userId))
            ->count();

        return [
            Stat::make('Mahasiswa Saya', $totalStudents)
                ->description('Total siswa di semua kelas')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Tugas Aktif', $activeAssignments)
                ->description('Deadline belum berakhir')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('warning'),

            Stat::make('Materi', $totalMaterials)
                ->description('Total materi terupload')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),
        ];
    }
}
