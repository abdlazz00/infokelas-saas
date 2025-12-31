<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get User Profile & Academic Data
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil kelas terakhir berdasarkan tanggal join
        // Menggunakan orderByPivot aman karena kita sudah fix logic joinClass
        $latestClass = $user->classrooms()->orderByPivot('joined_at', 'desc')->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nim' => $user->nim ?? '-',
                'university' => $latestClass->university ?? '-',
                'major' => $latestClass->major ?? '-',
                'semester' => $latestClass->semester ?? '-',
                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            ]
        ]);
    }

    /**
     * Update Profile Data
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($request->hasFile('avatar')) {
            // Hapus avatar lama jika perlu (opsional), lalu simpan yang baru
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            ]
        ]);
    }
}
