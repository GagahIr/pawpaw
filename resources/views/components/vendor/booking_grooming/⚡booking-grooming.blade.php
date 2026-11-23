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
        <flux:heading size="xl">Booking Grooming</flux:heading>
        <flux:subheading>Kelola booking grooming dari pelanggan.</flux:subheading>
    </div>

    <livewire:admin.datatables.grooming-bookings-table />
</div>
