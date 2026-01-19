<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon; // Pastikan import Carbon

class ScheduleController extends Controller
{
    /**
     * Get Student Schedules
     * Bisa filter hari ini dengan ?today=true
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $classroomIds = $user->classrooms()->pluck('classrooms.id');

        $query = Schedule::query()
            ->whereIn('classroom_id', $classroomIds)
            ->whereHas('subject', fn($q) => $q->where('is_active', true))
            ->with(['subject', 'classroom', 'subject.classroom.teacher:id,name']);

        if ($request->has('today')) {
            // dayOfWeekIso: 1 (Senin) s/d 7 (Minggu)
            $today = Carbon::now()->dayOfWeekIso;
            $query->where('day_of_week', $today);
        }

        $schedules = $query
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $formattedSchedules = $schedules->map(function ($schedule) {
            $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
            return [
                'id' => $schedule->id,
                'day' => $days[$schedule->day_of_week] ?? 'Unknown',
                'time' => substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5),
                'subject_name' => $schedule->subject->name,
                'subject_code' => $schedule->subject->code,
                'lecturer' => $schedule->subject->lecturer,
                'room' => $schedule->room,
                'class_name' => $schedule->classroom->name,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedSchedules
        ]);
    }
}
