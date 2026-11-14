<?php

namespace App\Policies;

use App\Enums\StaffPosition;
use App\Models\User;
use App\Models\VendorStaff;

class VendorStaffPolicy
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
    public function view(User $user, VendorStaff $vendorStaff): bool
    {
        return $user->vendorStaff() !== null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->staffPosition() === StaffPosition::Owner;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?VendorStaff $vendorStaff = null): bool
    {
        return $user->staffPosition() === StaffPosition::Owner;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ?VendorStaff $vendorStaff = null): bool
    {
        return $user->staffPosition() === StaffPosition::Owner;
    }
}
