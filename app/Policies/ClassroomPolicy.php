<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    /**
     * Menu Index: Siapa yang boleh lihat menu Classroom?
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'super_admin' || $user->role === 'admin_kelas';
    }

    /**
     * View Detail: Siapa yang boleh lihat detail kelas tertentu?
     */
    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->role === 'super_admin') return true;

        // Admin Kelas hanya boleh lihat jika dia pemiliknya (teacher_id)
        return $user->role === 'admin_kelas' && $classroom->teacher_id === $user->id;
    }

    /**
     * Create: Siapa yang boleh buat kelas?
     */
    public function create(User $user): bool
    {
        return $user->role === 'super_admin' || $user->role === 'admin_kelas';
    }

    /**
     * Edit: Siapa yang boleh edit?
     */
    public function update(User $user, Classroom $classroom): bool
    {
        if ($user->role === 'super_admin') return true;

        return $user->role === 'admin_kelas' && $classroom->teacher_id === $user->id;
    }

    /**
     * Delete: Siapa yang boleh hapus?
     */
    public function delete(User $user, Classroom $classroom): bool
    {
        if ($user->role === 'super_admin') return true;

        return $user->role === 'admin_kelas' && $classroom->teacher_id === $user->id;
    }
}
