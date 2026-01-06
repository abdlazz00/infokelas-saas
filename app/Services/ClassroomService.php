<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ClassroomService
{
    //Join Class
    public function joinClass(User $student, string $code): array
    {
        $classroom = Classroom::where('code', $code)->first();
        if (!$classroom) {
            throw new Exception('Kelas tidak ditemukan', 404);
        }

        if (!$classroom->is_active || $$classroom->subscription_status !== 'active') {
            throw new Exception("kelas ini sedang tidak aktif.", 403);
        }

        $alreadyjoined = $student->classrooms()
            ->where('classroom_id', $classroom->id)
            ->exists();

        if ($alreadyjoined) {
            throw new Exception('Anda sudah terdaftar dikelas ini.', 409);
        }

        $student->classrooms()->attach($classroom->id, [
            'joined_at' => now(),
        ]);

        return [
            'status' =>'success',
            'message' => 'Berhasil bergabung ke kelas ' . $classroom->name,
            'data' => $classroom
        ];
    }

    //Get List Subject
    public function getSubjects(string $classroomId): Collection
    {
        if (!Classroom::where('id', $classroomId)->exists()) {
            throw new Exception('Kelas tidak ditemukan', 404);

        }
        return Subject::query()
            ->where('is_active', true)
            ->where(function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId) // Matkul yang menempel langsung
                ->orWhereHas('schedules', fn ($q) => $q->where('classroom_id', $classroomId)); // Atau yang punya jadwal
            })
            ->distinct()
            ->get();
    }
}
