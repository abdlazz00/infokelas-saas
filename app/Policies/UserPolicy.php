<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     * (Wajib true agar Relation Manager bisa tampil)
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin_kelas']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasRole(['super_admin', 'admin_kelas']);
    }

    /**
     * Determine whether the user can create models.
     * (Wajib true agar tombol "Input Mahasiswa Baru" muncul)
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin_kelas']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasRole(['super_admin', 'admin_kelas']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role === 'super_admin';
    }
}
