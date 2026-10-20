<?php

namespace App\Policies;

use App\Enums\StaffPosition;
use App\Models\User;
use App\Models\VendorOperatingHour;
use Illuminate\Auth\Access\Response;

class VendorOperationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VendorOperatingHour $vendorOperatingHour): bool
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
    public function update(User $user, ?VendorOperatingHour $vendorOperatingHour = null): bool
    {
        return $user->staffPosition() === StaffPosition::Owner;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VendorOperatingHour $vendorOperatingHour): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VendorOperatingHour $vendorOperatingHour): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VendorOperatingHour $vendorOperatingHour): bool
    {
        return false;
    }
}
