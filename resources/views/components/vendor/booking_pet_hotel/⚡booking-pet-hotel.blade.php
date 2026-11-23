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
        <flux:heading size="xl">Booking Pet Hotel</flux:heading>
        <flux:subheading>Kelola booking pet hotel dari pelanggan.</flux:subheading>
    </div>

    <livewire:admin.datatables.pet-hotel-bookings-table />
</div>
