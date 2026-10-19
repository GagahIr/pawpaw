<?php

namespace App\Policies;

use App\Enums\StaffPosition;
use App\Models\User;
use App\Models\VendorService;

class VendorServicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->vendorStaff() !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VendorService $vendorService): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?VendorService $vendorService = null): bool
    {
        return $user->staffPosition() === StaffPosition::Owner;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VendorService $vendorService): bool
    {
        return $user->staffPosition() === StaffPosition::Owner;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VendorService $vendorService): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VendorService $vendorService): bool
    {
        return false;
    }
}
