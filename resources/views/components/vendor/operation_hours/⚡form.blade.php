<?php

use App\Models\Vendor;
use App\Enums\SystemMessage;
use App\Actions\Admin\VendorOperationHour\VendorOperationHourAction;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Flux\Flux;

new #[Layout('layouts::admin')] class extends Component
{
    public ?Vendor $vendor = null;

    public array $operatingHours = [];

    private array $daysOfWeek = [
        0 => 'Senin',
        1 => 'Selasa',
        2 => 'Rabu',
        3 => 'Kamis',
        4 => 'Jumat',
        5 => 'Sabtu',
        6 => 'Minggu',
    ];

    public function mount()
    {
        $this->vendor = auth()->user()->vendor;
        $this->loadOperatingHours();
    }

    public function loadOperatingHours()
    {
        $this->operatingHours = [];

        for ($day = 0; $day < 7; $day++) {
            $hour = $this->vendor->vendorOperatingHours()
                ->where('day_of_week', $day)
                ->first();

            $this->operatingHours[$day] = [
                'day_of_week' => $day,
                'day_name' => $this->daysOfWeek[$day],
                'open_time' => $hour?->open_time ?? '09:00',
                'close_time' => $hour?->close_time ?? '17:00',
                'is_closed' => $hour?->is_closed ?? false,
            ];
        }
    }

    public function copyToAll($sourceDay)
    {
        $source = $this->operatingHours[$sourceDay];
        
        for ($day = 0; $day < 7; $day++) {
            if ($day !== $sourceDay) {
                $this->operatingHours[$day]['open_time'] = $source['open_time'];
                $this->operatingHours[$day]['close_time'] = $source['close_time'];
                $this->operatingHours[$day]['is_closed'] = $source['is_closed'];
            }
        }

        Flux::toast('Jam operasional berhasil disalin ke semua hari', variant: 'success');
    }

    public function applyToWeekdays()
    {
        $mondaySettings = $this->operatingHours[0];
        
        for ($day = 1; $day < 5; $day++) {
            $this->operatingHours[$day]['open_time'] = $mondaySettings['open_time'];
            $this->operatingHours[$day]['close_time'] = $mondaySettings['close_time'];
            $this->operatingHours[$day]['is_closed'] = $mondaySettings['is_closed'];
        }

        Flux::toast('Jam operasional Senin berhasil diterapkan ke Selasa-Jumat', variant: 'success');
    }

    public function save(VendorOperationHourAction $vendorOperationHourAction)
    {
        $vendorOperationHourAction->execute($this->vendor->id, $this->operatingHours);
        Flux::toast(SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
        $this->loadOperatingHours();
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
            Jam Operasional
        </h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Kelola jam operasional {{ $vendor?->name }}
        </p>
    </div>

    <flux:card class="mb-6">
        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-2 mb-6 pb-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:button 
                wire:click="applyToWeekdays" 
                variant="outline" 
                size="sm"
                icon="clipboard-document"
            >
                Terapkan Senin ke Selasa-Jumat
            </flux:button>
        </div>

        <!-- Operating Hours List -->
        <div class="space-y-3">
            @foreach ($operatingHours as $day => $hour)
                <div 
                    class="flex flex-col md:flex-row md:items-center gap-4 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 transition-opacity"
                    :class="{ 'opacity-50 bg-zinc-50 dark:bg-zinc-800/50': {{ $hour['is_closed'] ? 'true' : 'false' }} }"
                >
                    <!-- Day Name & Toggle -->
                    <div class="flex items-center gap-4 md:w-48">
                        <div class="flex-1">
                            <flux:heading size="sm" class="font-semibold">
                                {{ $hour['day_name'] }}
                            </flux:heading>
                        </div>
                        <flux:switch
                            wire:model.live="operatingHours.{{ $day }}.is_closed"
                            label="Tutup"
                        />
                    </div>

                    <!-- Time Inputs -->
                    <div class="flex-1 flex items-center gap-3">
                        @if (!$hour['is_closed'])
                            <div class="flex items-center gap-2 flex-1">
                                <flux:input
                                    wire:model="operatingHours.{{ $day }}.open_time"
                                    type="time"
                                    class="flex-1"
                                />
                                <span class="text-zinc-500 dark:text-zinc-400">—</span>
                                <flux:input
                                    wire:model="operatingHours.{{ $day }}.close_time"
                                    type="time"
                                    class="flex-1"
                                />
                            </div>
                        @else
                            <div class="flex-1 text-red-600 dark:text-red-400 font-medium">
                                Tutup
                            </div>
                        @endif
                    </div>

                    <!-- Copy Action -->
                    <div class="flex items-center gap-2 md:w-auto">
                        <flux:button
                            wire:click="copyToAll({{ $day }})"
                            variant="ghost"
                            size="sm"
                            icon="clipboard"
                            class="text-blue-600 hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-blue-900/30"
                        >
                            Salin waktu ini ke semua hari
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Save Button -->
        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button wire:click="save" variant="primary">
                Simpan Perubahan
            </flux:button>
        </div>
    </flux:card>
</div>
