<?php

namespace App\Policies;

use App\Models\User;

/**
 * Maps Filament/Laravel policy abilities onto "{action} {module}" permissions.
 */
abstract class ModulePolicy
{
    protected string $module;

    public function viewAny(User $user): bool
    {
        return $user->can("view {$this->module}");
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can("create {$this->module}");
    }

    public function update(User $user): bool
    {
        return $user->can("update {$this->module}");
    }

    public function reorder(User $user): bool
    {
        return $this->update($user);
    }

    public function delete(User $user): bool
    {
        return $user->can("delete {$this->module}");
    }

    public function deleteAny(User $user): bool
    {
        return $this->delete($user);
    }

    public function restore(User $user): bool
    {
        return $this->delete($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->delete($user);
    }

    public function forceDelete(User $user): bool
    {
        return $this->delete($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->delete($user);
    }

    public function replicate(User $user): bool
    {
        return $this->create($user);
    }
}
