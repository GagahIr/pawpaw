<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.client')] class extends Component {
    public $userClient;

    public function mount()
    {
        $this->userClient = auth()->user()->userClient;
    }

    #[Computed]
    public function shouldShowFakeBookingAlert()
    {
        return $this->userClient && $this->userClient->count_fake_booking >= 3;
    }

    #[Computed]
    public function fakeBookingWarningMessage()
    {
        if (!$this->userClient) {
            return '';
        }

        $count = $this->userClient->count_fake_booking;
        $remaining = 5 - $count;

        return "Anda memiliki {$count} pemesanan palsu. Mencapai 5 akan menangguhkan akun Anda. ({$remaining} tersisa)";
    }
};
?>

<div class="clinic-page">
    @include('components.client.components.clinic-atmosphere')
    <div class="clinic-wrap">
        <div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
            @if ($this->shouldShowFakeBookingAlert)
                <div class="sticky top-0 z-50 bg-amber-50 border-b border-amber-200 px-4 py-3 sm:px-6 -mx-4 sm:-mx-6 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-amber-800">{{ $this->fakeBookingWarningMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <section class="flex min-h-[60vh] items-center">
        <div class="w-full rounded-2xl border border-zinc-200 bg-white p-6 sm:p-10">
            <p class="text-sm font-semibold text-[#1a5c40]">Dashboard Pelanggan</p>
            <h1 class="mt-3 break-words text-3xl font-bold text-zinc-900">Halo, {{ auth()->user()->name }}!</h1>
            <p class="mt-4 text-zinc-600">Selamat datang di PawPaw. Akun Anda sudah berhasil masuk.</p>
            <p class="mt-2 text-sm text-zinc-500">Halaman dashboard sementara ini akan dilengkapi dengan fitur pelanggan.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <flux:button href="{{ route('landing_page') }}" variant="ghost">Kembali ke Beranda</flux:button>
                <form method="POST" action="{{ route('client.logout') }}">
                    @csrf
                    <flux:button type="submit" variant="primary" class="!bg-[#1a5c40]">Logout</flux:button>
                </form>
            </div>
        </div>
    </section>
        </div>
    </div>
    @include('components.client.components.clinic-footer')
</div>
