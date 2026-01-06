<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasUlids;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // --- LOGIC ROLE & LOGIN ---
    public function canAccessPanel(Panel $panel): bool
    {
        // Sementara semua user boleh login ke admin panel
        // Nanti kita batasi menunya via Policy / Resource Scope
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'admin_kelas';
    }

    public function isStudent(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function classroomsAsTeacher(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'class_students', 'student_id', 'classroom_id')
            ->using(ClassStudent::class) // <--- TAMBAHKAN INI
            ->withPivot('joined_at')
            ->withTimestamps();
    }
    public function hasRole($role): bool
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }

        return $this->role === $role;
    }
}
