<?php

namespace App\Enums;

enum DocumentType: string
{
    case DriverLicense = 'driver_license';
    case NationalId = 'national_id';
    case Insurance = 'insurance';
    case BusinessRegistration = 'business_registration';
}
