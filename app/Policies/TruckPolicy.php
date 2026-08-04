<?php

namespace App\Policies;

use App\Models\Truck;
use App\Models\User;

class TruckPolicy
{
    /**
     * Admins manage everything through the Filament panel.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->transporterProfile !== null;
    }

    public function update(User $user, Truck $truck): bool
    {
        return $user->transporterProfile?->id === $truck->transporter_profile_id;
    }

    public function delete(User $user, Truck $truck): bool
    {
        return $this->update($user, $truck);
    }
}
