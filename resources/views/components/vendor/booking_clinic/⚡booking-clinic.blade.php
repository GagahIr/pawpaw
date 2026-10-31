<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin')] class extends Component {
    //
};
?>

<div class="space-y-6 py-6">
    <div>
        <flux:heading size="xl">Booking Clinic</flux:heading>
        <flux:subheading>Kelola booking klinik dari pelanggan.</flux:subheading>
    </div>

    <livewire:admin.datatables.clinic-bookings-table />
</div>
