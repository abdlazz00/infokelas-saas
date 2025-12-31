<?php
//
//namespace App\Http\Controllers\Api;
//
//use App\Http\Controllers\Controller;
//use App\Models\Assignment;
//use App\Models\Classroom;
//use App\Models\Material;
//use App\Models\Schedule;
//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\DB;
//
//class AcademicController extends Controller
//{
//    // --- 1. DAFTAR KELAS (FIXED) ---
//    public function myClassrooms(Request $request)
//    {
//        $user = $request->user();
//
//        // Query data kelas
//        $classrooms = $user->classrooms()
//            ->with('teacher:id,name')
//            ->get();
//
//        // --- DEBUG RESPONSE ---
//        // Kita kirim data tambahan untuk mengecek ID User yang sedang login
//        return response()->json([
//            'status' => 'success',
//            'debug_info' => [
//                'logged_in_user_id' => $user->id,      // ID User yang sedang login
//                'logged_in_user_name' => $user->name,  // Nama User
//                'total_classes_found' => $classrooms->count(), // Jumlah kelas yang ketemu
//            ],
//            'data' => $classrooms
//        ]);
//    }
//
//    // --- 2. JADWAL KULIAH ---
//    public function mySchedules(Request $request)
//    {
//        $user = $request->user();
//        $classroomIds = $user->classrooms()->pluck('classrooms.id');
//
//        $schedules = Schedule::query()
//            ->whereIn('classroom_id', $classroomIds)
//            ->whereHas('subject', fn($q) => $q->where('is_active', true))
//            ->with(['subject', 'classroom', 'subject.classroom.teacher:id,name'])
//            ->orderBy('day_of_week', 'asc')
//            ->orderBy('start_time', 'asc')
//            ->get();
//
//        $formattedSchedules = $schedules->map(function ($schedule) {
//            $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
//            return [
//                'id' => $schedule->id,
//                'day' => $days[$schedule->day_of_week] ?? 'Unknown',
//                'time' => substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5),
//                'subject_name' => $schedule->subject->name,
//                'subject_code' => $schedule->subject->code,
//                'lecturer' => $schedule->subject->lecturer,
//                'room' => $schedule->room,
//                'class_name' => $schedule->classroom->name,
//            ];
//        });
//
//        return response()->json(['status' => 'success', 'data' => $formattedSchedules]);
//    }
//
//    // --- 3. GABUNG KELAS (FIXED: Tambah joined_at) ---
//    public function joinClass(Request $request)
//    {
//        $request->validate([
//            'code' => 'required|string',
//        ]);
//
//        $user = $request->user();
//        $classroom = Classroom::where('code', $request->code)->first();
//
//        if (!$classroom) {
//            return response()->json(['status' => 'error', 'message' => 'Kode kelas tidak ditemukan.'], 404);
//        }
//
//        // Cek apakah sudah terdaftar
//        $alreadyJoined = $user->classrooms()->where('classroom_id', $classroom->id)->exists();
//
//        if ($alreadyJoined) {
//            return response()->json(['status' => 'error', 'message' => 'Anda sudah terdaftar di kelas ini.'], 409);
//        }
//
//        // PERBAIKAN PENTING:
//        // Kita WAJIB mengisi 'joined_at' agar tidak NULL di database
//        $user->classrooms()->attach($classroom->id, ['joined_at' => now()]);
//
//        return response()->json([
//            'status' => 'success',
//            'message' => 'Berhasil bergabung ke kelas ' . $classroom->name,
//        ]);
//    }
//
//    // --- 4. LIST MATERI ---
//    public function getMaterials($subjectId)
//    {
//        $materials = Material::where('subject_id', $subjectId)
//            ->whereHas('subject', fn($q) => $q->where('is_active', true))
//            ->orderBy('created_at', 'desc')
//            ->get()
//            ->map(function ($material) {
//                return [
//                    'id' => $material->id,
//                    'title' => $material->title,
//                    'description' => $material->description,
//                    'type' => $material->file_path ? 'file' : 'link',
//                    'file_url' => $material->file_path ? asset('storage/' . $material->file_path) : null,
//                    'link_url' => $material->link_url,
//                    'created_at' => $material->created_at->format('d M Y'),
//                ];
//            });
//
//        return response()->json(['status' => 'success', 'data' => $materials]);
//    }
//
//    // --- 5. LIST TUGAS ---
//    public function getAssignments($subjectId)
//    {
//        $assignments = Assignment::where('subject_id', $subjectId)
//            ->whereHas('subject', fn($q) => $q->where('is_active', true))
//            ->orderBy('deadline', 'asc')
//            ->get()
//            ->map(function ($task) {
//                return [
//                    'id' => $task->id,
//                    'title' => $task->title,
//                    'description' => $task->description,
//                    'deadline' => $task->deadline->format('d M Y H:i'),
//                    'is_overdue' => $task->deadline < now(),
//                    'created_at' => $task->created_at->format('d M Y'),
//                ];
//            });
//
//        return response()->json(['status' => 'success', 'data' => $assignments]);
//    }
//
//    // --- 6. DETAIL KELAS ---
//    public function detailClassroom($id)
//    {
//        $classroom = Classroom::with('teacher')->find($id);
//
//        if (!$classroom) {
//            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan'], 404);
//        }
//
//        return response()->json(['status' => 'success', 'data' => $classroom]);
//    }
//
//    // --- 7. MATERI & TUGAS PER KELAS ---
//    public function materialsByClassroom($id)
//    {
//        $classroom = Classroom::find($id);
//        if (!$classroom) {
//            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan'], 404);
//        }
//
//        $materials = Material::query()
//            ->where(function($query) use ($classroom, $id) {
//                $query->where('classroom_id', $id);
//                if (!empty($classroom->subject_id)) {
//                    $query->orWhere('subject_id', $classroom->subject_id);
//                }
//            })
//            ->orderBy('created_at', 'desc')
//            ->get()
//            ->map(function ($material) {
//                return [
//                    'id' => $material->id,
//                    'title' => $material->title,
//                    'description' => $material->description,
//                    'file_path' => $material->file_path,
//                    'file_url' => $material->file_path ? asset('storage/' . $material->file_path) : null,
//                    'link_url' => $material->link_url,
//                    'created_at' => $material->created_at->format('d M Y'),
//                ];
//            });
//
//        return response()->json(['status' => 'success', 'data' => $materials]);
//    }
//
//    public function subjectsInClassroom($classroomId)
//    {
//        $schedules = Schedule::where('classroom_id', $classroomId)->with('subject')->get();
//        $subjects = $schedules->pluck('subject')->unique('id')->values();
//        return response()->json(['status' => 'success', 'data' => $subjects]);
//    }
//
//    public function assignmentsByClassroom($id)
//    {
//        $classroom = Classroom::find($id);
//        if (!$classroom) {
//            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan'], 404);
//        }
//
//        $assignments = \App\Models\Assignment::query()
//            ->where(function($query) use ($classroom, $id) {
//                $query->where('classroom_id', $id);
//                if (!empty($classroom->subject_id)) {
//                    $query->orWhere('subject_id', $classroom->subject_id);
//                }
//            })
//            ->orderBy('deadline', 'asc')
//            ->get()
//            ->map(function ($task) {
//                $deadline = \Carbon\Carbon::parse($task->deadline);
//                $isOverdue = \Carbon\Carbon::now()->greaterThan($deadline);
//                return [
//                    'id' => $task->id,
//                    'title' => $task->title,
//                    'description' => $task->description,
//                    'deadline' => $deadline->format('d M Y, H:i'),
//                    'is_overdue' => $isOverdue,
//                    'time_remaining' => $isOverdue ? 'Terlambat' : $deadline->diffForHumans(),
//                    'created_at' => $task->created_at->format('d M Y'),
//                ];
//            });
//
//        return response()->json(['status' => 'success', 'data' => $assignments]);
//    }
//
//    public function detailAssignment($id)
//    {
//        $assignment = Assignment::find($id);
//        if (!$assignment) {
//            return response()->json(['status' => 'error', 'message' => 'Tugas tidak ditemukan'], 404);
//        }
//        return response()->json(['status' => 'success', 'data' => ['assignment' => $assignment]]);
//    }
//
//    // --- 8. GET PROFILE (FIXED) ---
//    public function profile(Request $request)
//    {
//        $user = $request->user();
//
//        // Ambil kelas terakhir berdasarkan tanggal join
//        // Karena joinClass sudah diperbaiki (ada joined_at), kita bisa pakai orderByPivot lagi
//        $latestClass = $user->classrooms()->orderByPivot('joined_at', 'desc')->first();
//
//        return response()->json([
//            'status' => 'success',
//            'data' => [
//                'id' => $user->id,
//                'name' => $user->name,
//                'email' => $user->email,
//                'nim' => $user->nim ?? '-',
//                'university' => $latestClass->university ?? '-',
//                'major' => $latestClass->major ?? '-',
//                'semester' => $latestClass->semester ?? '-',
//                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
//            ]
//        ]);
//    }
//
//    // --- 9. UPDATE PROFILE ---
//    public function updateProfile(Request $request)
//    {
//        $user = $request->user();
//        $request->validate([
//            'name' => 'nullable|string|max:255',
//            'email' => 'nullable|email|unique:users,email,' . $user->id,
//            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
//        ]);
//
//        if ($request->filled('name')) $user->name = $request->name;
//        if ($request->filled('email')) $user->email = $request->email;
//        if ($request->hasFile('avatar')) {
//            $path = $request->file('avatar')->store('avatars', 'public');
//            $user->avatar = $path;
//        }
//        $user->save();
//
//        return response()->json([
//            'status' => 'success',
//            'message' => 'Profil berhasil diperbarui.',
//            'data' => [
//                'name' => $user->name,
//                'email' => $user->email,
//                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
//            ]
//        ]);
//    }
//}
