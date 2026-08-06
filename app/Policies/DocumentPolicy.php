<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Admins review everything through the Filament panel.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function view(User $user, Document $document): bool
    {
        $documentable = $document->documentable;

        if ($documentable instanceof TransporterProfile) {
            return $documentable->user_id === $user->id;
        }

        if ($documentable instanceof Truck) {
            return $documentable->transporterProfile->user_id === $user->id;
        }

        return false;
    }
}
