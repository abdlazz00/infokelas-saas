<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Services\ClassroomService; // Import Service
use Illuminate\Http\Request;
use Exception;

class ClassroomController extends Controller
{
    protected $classroomService;

    // Inject Service
    public function __construct(ClassroomService $classroomService)
    {
        $this->classroomService = $classroomService;
    }

    /**
     * List Kelas Saya
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil kelas yang diikuti user (Pivot)
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
     * Gabung Kelas (Join Class) - Via Service
     */
    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            // Panggil logic di Service
            $result = $this->classroomService->joinClass(
                $request->user(),
                $request->code
            );

            return response()->json($result, 200);

        } catch (Exception $e) {
            // Tangkap error logic (misal: kode salah, sudah join, expired)
            // Gunakan getCode() dari Exception jika valid HTTP code, default 500
            $statusCode = ($e->getCode() >= 100 && $e->getCode() < 600) ? $e->getCode() : 500;

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * List Mata Kuliah - Via Service
     */
    public function subjects($id)
    {
        try {
            // Panggil logic optimasi di Service
            $subjects = $this->classroomService->getSubjects($id);

            return response()->json([
                'status' => 'success',
                'data' => $subjects
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
