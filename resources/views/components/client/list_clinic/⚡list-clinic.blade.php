<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.client')] class extends Component {
    //
};
?>

<div class="clinic-page">
    @include('components.client.components.clinic-atmosphere')
    <div class="clinic-wrap">
        <div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
            {{-- Content akan ditambahkan di sini --}}
        </div>
    </div>
    @include('components.client.components.clinic-footer')
</div>
