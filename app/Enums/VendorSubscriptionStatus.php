<?php

namespace App\Enums;

enum VendorSubscriptionStatus: string
{
    case Inactive = 'inactive';
    case Active = 'active';
    case Suspended = 'suspended';
}
