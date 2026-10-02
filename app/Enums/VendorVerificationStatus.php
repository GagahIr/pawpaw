<?php

namespace App\Enums;

enum VendorVerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Approved => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }
}
