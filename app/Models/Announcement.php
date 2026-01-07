<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory, HasUlids;

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     * Sesuaikan dengan migration yang kita buat tadi.
     */
    protected $fillable = [
        'user_id',
        'classroom_id',
        'title',
        'content',
        'type',      // info, warning, danger
        'image',     // path gambar
        'is_active', // boolean (true/false)
        'wa_group_id',
    ];

    /**
     * Konversi tipe data otomatis saat diambil dari database.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function waGroup(): BelongsTo
    {
        return $this->belongsTo(WaGroup::class, 'wa_group_id');
    }
}
