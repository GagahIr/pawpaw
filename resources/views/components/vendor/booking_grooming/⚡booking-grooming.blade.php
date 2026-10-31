<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin')] class extends Component {
    //
};
?>

<div class="space-y-6 py-6">
    <div>
        <flux:heading size="xl">Booking Grooming</flux:heading>
        <flux:subheading>Kelola booking grooming dari pelanggan.</flux:subheading>
    </div>

    <livewire:admin.datatables.grooming-bookings-table />
</div>
