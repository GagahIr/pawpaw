<?php

namespace App\View\Components;

use App\Actions\Admin\Booking\GetVendorPendingBookingCountsAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;

class VendorSidebar extends Component
{
    public array $bookingCounts;

    public string $vendorLogoUrl;

    public string $vendorInitial;

    public string $vendorName;

    public function __construct(GetVendorPendingBookingCountsAction $action)
    {
        $vendor = auth()->user()->vendor;

        $this->bookingCounts = $action->execute();
        $this->vendorLogoUrl = $vendor->logo ? Storage::url($vendor->logo) : '';
        $this->vendorName = $vendor->name ?? '';
        $this->vendorInitial = strtoupper(substr($this->vendorName ?: 'V', 0, 1));
    }

    public function render()
    {
        return view('components.vendor-sidebar');
    }
}
