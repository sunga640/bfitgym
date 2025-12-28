<?php

namespace App\Policies;

use App\Models\ClassBooking;
use App\Models\User;

class ClassBookingPolicy extends BranchScopedPolicy
{
    /**
     * Determine whether the user can view any class bookings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view class bookings') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can view the class booking.
     */
    public function view(User $user, ClassBooking $class_booking): bool
    {
        return $user->hasPermissionTo('view class bookings') && $this->belongsToUserBranch($user, $class_booking);
    }

    /**
     * Determine whether the user can create class bookings.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create class bookings') && $this->hasCurrentBranchAccess($user);
    }

    /**
     * Determine whether the user can update the class booking.
     */
    public function update(User $user, ClassBooking $class_booking): bool
    {
        return $user->hasPermissionTo('create class bookings') && $this->belongsToUserBranch($user, $class_booking);
    }

    /**
     * Determine whether the user can cancel the class booking.
     */
    public function cancel(User $user, ClassBooking $class_booking): bool
    {
        return $user->hasPermissionTo('cancel class bookings') && $this->belongsToUserBranch($user, $class_booking);
    }

    /**
     * Determine whether the user can delete the class booking.
     */
    public function delete(User $user, ClassBooking $class_booking): bool
    {
        return $user->hasPermissionTo('cancel class bookings') && $this->belongsToUserBranch($user, $class_booking);
    }
}

