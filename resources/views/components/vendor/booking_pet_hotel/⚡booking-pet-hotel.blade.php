<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin')] class extends Component {
    //
};
?>

<div class="space-y-6 py-6">
    <div>
        <flux:heading size="xl">Booking Pet Hotel</flux:heading>
        <flux:subheading>Kelola booking pet hotel dari pelanggan.</flux:subheading>
    </div>

    <livewire:admin.datatables.pet-hotel-bookings-table />
</div>
