<?php

use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Actions\Client\Booking\GetActiveBookingsAction;

new #[Layout('layouts.client')] class extends Component {
    public function with(): array
    {
        $userClientId = auth('client')->user()?->userClient?->id;

        return ['bookings' => $userClientId ? app(GetActiveBookingsAction::class)->execute($userClientId) : collect()];
    }
};
?>

<div class="min-h-screen py-12 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Reservasi Saya</h1>
            <p class="text-gray-600 mt-1">Daftar booking yang sedang aktif</p>
        </div>

        @if ($bookings->isEmpty())
            {{-- Empty State --}}
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-900">Belum ada reservasi aktif</p>
                <p class="text-gray-500 mt-1">Booking layanan klinik, grooming, atau pet hotel terlebih dahulu</p>
                <a href="{{ route('client.clinic') }}"
                    class="inline-block mt-6 px-6 py-2.5 bg-[#EE6D52] text-white rounded-lg font-medium hover:bg-[#d95b42] transition-colors">
                    Cari Klinik
                </a>
            </div>
        @else
            {{-- Booking Cards --}}
            <div class="space-y-4">
                @foreach ($bookings as $booking)
                    <div
                        class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                {{-- Left: Info --}}
                                <div class="min-w-0 flex-1">
                                    {{-- Category Badge + Status --}}
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        @php
                                            $typeBg = match ($booking['type']) {
                                                'clinic' => 'bg-blue-50 text-blue-700',
                                                'grooming' => 'bg-purple-50 text-purple-700',
                                                'pet_hotel' => 'bg-teal-50 text-teal-700',
                                                default => 'bg-gray-50 text-gray-700',
                                            };
                                            $statusBg = match ($booking['status']) {
                                                'pending' => 'bg-amber-100 text-amber-800',
                                                'confirmed' => 'bg-green-100 text-green-800',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                            $statusLabel = match ($booking['status']) {
                                                'pending' => 'Menunggu Konfirmasi',
                                                'confirmed' => 'Dikonfirmasi',
                                                default => ucfirst($booking['status']),
                                            };
                                        @endphp
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeBg }}">
                                            {{ $booking['category_label'] }}
                                        </span>
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBg }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    {{-- Service Item Name --}}
                                    <h3 class="text-lg font-semibold text-gray-900 truncate">
                                        {{ $booking['service_item_name'] }}
                                    </h3>

                                    {{-- Vendor Name --}}
                                    <p class="text-sm text-gray-600 mt-0.5">
                                        {{ $booking['vendor_name'] }}
                                    </p>

                                    {{-- Date & Time --}}
                                    <div class="flex items-center gap-1.5 mt-3 text-sm text-gray-700">
                                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ $booking['date_label'] }}</span>
                                    </div>
                                </div>

                                {{-- Right: Price --}}
                                @if ($booking['price'])
                                    <div class="text-right shrink-0">
                                        <p class="text-lg font-bold text-[#EE6D52]">
                                            Rp {{ number_format($booking['price'], 0, ',', '.') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
