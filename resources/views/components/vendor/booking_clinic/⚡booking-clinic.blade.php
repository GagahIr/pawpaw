<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin')] class extends Component {
    //
};
?>


<div x-data x-on:open-whatsapp.window="window.open($event.detail.url, '_blank', 'noopener,noreferrer')"
    class="space-y-6 py-6">
    <div>
        <flux:heading size="xl">Booking Clinic</flux:heading>
        <flux:subheading>Kelola booking klinik dari pelanggan.</flux:subheading>
    </div>

    <livewire:admin.datatables.clinic-bookings-table />
</div>
