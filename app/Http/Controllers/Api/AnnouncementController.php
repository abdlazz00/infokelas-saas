<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Get List of Active Announcements
     */
    public function index(Request $request)
    {
        $query = Announcement::with('author:id,name')
            ->where('is_active', true)
            ->latest();

        // Fitur Limit (Opsional)
        // Jika frontend kirim ?limit=3, maka ambil 3 saja.
        if ($request->has('limit')) {
            $query->limit($request->query('limit'));
        }

        $announcements = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'type' => $item->type,
                'image_url' => $item->image ? asset('storage/' . $item->image) : null,
                'author' => $item->author->name ?? 'Admin',
                'created_at' => $item->created_at->diffForHumans(),
                'date' => $item->created_at->format('d M Y'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }
}
