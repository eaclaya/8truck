<?php

namespace App\Enums;

enum TruckAvailability: string
{
    case Available = 'available';
    case Busy = 'busy';
    case Inactive = 'inactive';
}
