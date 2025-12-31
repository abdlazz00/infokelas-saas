<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    /**
     * List Kelas Saya (My Classrooms)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $classrooms = $user->classrooms()
            ->with('teacher:id,name')
            ->orderByPivot('joined_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $classrooms
        ]);
    }

    /**
     * Detail Satu Kelas
     */
    public function show($id)
    {
        $classroom = Classroom::with('teacher')->find($id);

        if (!$classroom) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $classroom]);
    }

    /**
     * Gabung Kelas (Join Class)
     */
    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        $classroom = Classroom::where('code', $request->code)->first();

        if (!$classroom) {
            return response()->json(['status' => 'error', 'message' => 'Kode kelas tidak ditemukan.'], 404);
        }

        // Cek apakah sudah terdaftar
        $alreadyJoined = $user->classrooms()->where('classroom_id', $classroom->id)->exists();

        if ($alreadyJoined) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah terdaftar di kelas ini.'], 409);
        }

        // WAJIB: Isi joined_at dengan waktu sekarang
        $user->classrooms()->attach($classroom->id, ['joined_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil bergabung ke kelas ' . $classroom->name,
        ]);
    }

    /**
     * List Mata Kuliah dalam satu Kelas
     */
    public function subjects($id)
    {
        if (!Classroom::where('id', $id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $subjects = Subject::whereHas('schedules', function ($q) use ($id) {
            $q->where('classroom_id', $id);
        })
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $subjects
        ]);
    }
}
