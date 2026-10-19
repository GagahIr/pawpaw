<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::admin')] class extends Component {
    //
};
?>

<div class="p-6">
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    Selamat Datang {{ auth()->user()->role->name }}, {{ auth()->user()->name }}!
                </h1>
            </div>
        </div>
    </div>
</div>
