<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaGroup;
use Illuminate\Auth\Access\Response;

class WaGroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin perlu akses untuk input JID
        return $user->role === 'super_admin' || $user->role === 'admin_kelas';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WaGroup $waGroup): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin_kelas' && $waGroup->classroom->teacher_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'super_admin' || $user->role === 'admin_kelas';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WaGroup $waGroup): bool
    {
        // Super Admin boleh update (Input JID)
        // Admin Kelas boleh update (Ganti Nama Group) milik sendiri
        return $user->role === 'super_admin' || ($user->role === 'admin_kelas' && $waGroup->classroom->teacher_id === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WaGroup $waGroup): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin_kelas' && $waGroup->classroom->teacher_id === $user->id);
    }

    public function restore(User $user, WaGroup $waGroup): bool
    {
        return false;
    }
    public function forceDelete(User $user, WaGroup $waGroup): bool
    {
        return false;
    }
}
