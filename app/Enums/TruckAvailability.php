<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TruckAvailability: string implements HasLabel
{
    case Available = 'available';
    case Busy = 'busy';
    case Inactive = 'inactive';

    public function getLabel(): string
    {
        return __('ui.'.match ($this) {
            self::Available => 'Available',
            self::Busy => 'Busy',
            self::Inactive => 'Inactive',
        });
    }
}
