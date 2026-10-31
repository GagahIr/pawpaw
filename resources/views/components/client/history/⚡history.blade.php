<?php

use App\Actions\Client\Booking\GetHistoryBookingsAction;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.client')] class extends Component
{
    public function with(GetHistoryBookingsAction $action): array
    {
        $userClientId = auth('client')->user()?->userClient?->id;

        if (! $userClientId) {
            return ['bookings' => collect()];
        }

        return [
            'bookings' => $action->execute($userClientId),
        ];
    }
};
?>

<div class="min-h-screen py-12 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Riwayat Reservasi</h1>
            <p class="text-gray-600 mt-1">Daftar booking yang telah selesai atau dibatalkan</p>
        </div>

        @if ($bookings->isEmpty())
            {{-- Empty State --}}
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-900">Belum ada riwayat reservasi</p>
                <p class="text-gray-500 mt-1">Booking yang telah selesai atau dibatalkan akan muncul di sini</p>
                <a href="{{ route('client.clinic') }}"
                    class="inline-block mt-6 px-6 py-2.5 bg-[#EE6D52] text-white rounded-lg font-medium hover:bg-[#d95b42] transition-colors">
                    Cari Klinik
                </a>
            </div>
        @else
            {{-- Booking Cards --}}
            <div class="space-y-4">
                @foreach ($bookings as $booking)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                {{-- Left: Info --}}
                                <div class="min-w-0 flex-1">
                                    {{-- Category Badge + Status --}}
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        @php
                                            $typeBg = match($booking['type']) {
                                                'clinic' => 'bg-blue-50 text-blue-700',
                                                'grooming' => 'bg-purple-50 text-purple-700',
                                                'pet_hotel' => 'bg-teal-50 text-teal-700',
                                                default => 'bg-gray-50 text-gray-700',
                                            };
                                            $statusBg = match($booking['status']) {
                                                'completed' => 'bg-emerald-100 text-emerald-800',
                                                'cancelled' => 'bg-zinc-100 text-zinc-700',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                            $statusLabel = match($booking['status']) {
                                                'completed' => 'Selesai',
                                                'cancelled' => 'Dibatalkan',
                                                'rejected' => 'Ditolak',
                                                default => ucfirst($booking['status']),
                                            };
                                        @endphp
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeBg }}">
                                            {{ $booking['category_label'] }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBg }}">
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
                                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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