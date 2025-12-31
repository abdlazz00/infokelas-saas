<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    /**
     * Get Assignments by Subject ID
     * Pengganti: getAssignments($subjectId)
     */
    public function index(Request $request)
    {
        $subjectId = $request->query('subject_id') ?? $request->route('id');

        if (!$subjectId) {
            return response()->json(['status' => 'error', 'message' => 'Subject ID required'], 400);
        }

        $assignments = Assignment::where('subject_id', $subjectId)
            ->whereHas('subject', fn($q) => $q->where('is_active', true))
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'deadline' => $task->deadline->format('d M Y H:i'),
                    'is_overdue' => $task->deadline < now(),
                    'created_at' => $task->created_at->format('d M Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $assignments]);
    }

    /**
     * Get Assignments by Classroom ID
     * Pengganti: assignmentsByClassroom($id)
     */
    public function byClassroom($id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $assignments = Assignment::query()
            ->where(function($query) use ($classroom, $id) {
                $query->where('classroom_id', $id);
                if (!empty($classroom->subject_id)) {
                    $query->orWhere('subject_id', $classroom->subject_id);
                }
            })
            ->orderBy('deadline', 'asc')
            ->get()
            ->map(function ($task) {
                $deadline = Carbon::parse($task->deadline);
                $isOverdue = Carbon::now()->greaterThan($deadline);

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'deadline' => $deadline->format('d M Y, H:i'),
                    'is_overdue' => $isOverdue,
                    'time_remaining' => $isOverdue ? 'Terlambat' : $deadline->diffForHumans(),
                    'created_at' => $task->created_at->format('d M Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $assignments]);
    }

    /**
     * Detail Tugas
     * Pengganti: detailAssignment($id)
     */
    public function show($id)
    {
        $assignment = Assignment::find($id);
        if (!$assignment) {
            return response()->json(['status' => 'error', 'message' => 'Tugas tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => ['assignment' => $assignment]]);
    }
}
