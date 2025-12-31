<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Get Materials by Subject ID
     * Pengganti: getMaterials($subjectId)
     */
    public function index(Request $request)
    {
        // Kita gunakan query parameter ?subject_id=... agar lebih RESTful
        // Tapi jika ingin support route lama /materials/{id}, kita sesuaikan di routes.
        // Di sini saya buat flexible menerima parameter langsung.

        $subjectId = $request->query('subject_id') ?? $request->route('id');

        if (!$subjectId) {
            return response()->json(['status' => 'error', 'message' => 'Subject ID required'], 400);
        }

        $materials = Material::where('subject_id', $subjectId)
            ->whereHas('subject', fn($q) => $q->where('is_active', true))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($material) {
                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'description' => $material->description,
                    'type' => $material->file_path ? 'file' : 'link',
                    'file_url' => $material->file_path ? asset('storage/' . $material->file_path) : null,
                    'link_url' => $material->link_url,
                    'created_at' => $material->created_at->format('d M Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $materials]);
    }

    /**
     * Get Materials by Classroom ID
     * Pengganti: materialsByClassroom($id)
     */
    public function byClassroom($id)
    {
        $classroom = Classroom::find($id);
        if (!$classroom) {
            return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan'], 404);
        }

        $materials = Material::query()
            ->where(function($query) use ($classroom, $id) {
                $query->where('classroom_id', $id);
                if (!empty($classroom->subject_id)) {
                    $query->orWhere('subject_id', $classroom->subject_id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($material) {
                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'description' => $material->description,
                    'file_path' => $material->file_path,
                    'file_url' => $material->file_path ? asset('storage/' . $material->file_path) : null,
                    'link_url' => $material->link_url,
                    'created_at' => $material->created_at->format('d M Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $materials]);
    }
    public function show($id)
    {
        // Cari materi berdasarkan ID
        $material = Material::with('subject')->find($id);

        if (!$material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Materi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $material->id,
                'title' => $material->title,
                'description' => $material->description,
                'type' => $material->file_path ? 'file' : 'link',
                'file_url' => $material->file_path ? asset('storage/' . $material->file_path) : null,
                'link_url' => $material->link_url,
                'subject_name' => $material->subject->name ?? '-',
                'created_at' => $material->created_at->format('d M Y'),
            ]
        ]);
    }
}
