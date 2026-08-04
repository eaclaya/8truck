<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentType: string implements HasLabel
{
    case DriverLicense = 'driver_license';
    case NationalId = 'national_id';
    case Insurance = 'insurance';
    case BusinessRegistration = 'business_registration';

    public function getLabel(): string
    {
        return __('ui.'.match ($this) {
            self::DriverLicense => 'Driver license',
            self::NationalId => 'National ID',
            self::Insurance => 'Insurance',
            self::BusinessRegistration => 'Business registration',
        });
    }
}
