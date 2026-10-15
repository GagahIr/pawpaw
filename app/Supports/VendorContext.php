<?php

namespace App\Supports;

use App\Models\Vendor;

class VendorContext
{
    protected ?Vendor $vendor = null;

    public function set(Vendor $vendor): void
    {
        $this->vendor = $vendor;
    }

    public function get(): ?Vendor
    {
        return $this->vendor;
    }

    public function id(): ?int
    {
        return $this->vendor?->id;
    }

    public function check(): bool
    {
        return $this->vendor !== null;
    }
}
