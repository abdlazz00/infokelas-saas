<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classroom extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($classroom) {
            // Jika code kosong, buatkan random string 5 karakter (Huruf Besar)
            if (empty($classroom->code)) {
                $classroom->code = strtoupper(Str::random(5));
            }
        });
    }

    // Pemilik Kelas (Dosen)
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Daftar Mahasiswa
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_students', 'classroom_id', 'student_id')
            ->withPivot('joined_at');
    }

    // --- RELASI AKADEMIK ---
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function waGroups(): HasMany
    {
        return $this->hasMany(WaGroup::class);
    }
}
