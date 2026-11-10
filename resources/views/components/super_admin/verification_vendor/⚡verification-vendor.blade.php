<?php

use App\Enums\SystemMessage;
use App\Enums\VendorVerificationStatus;
use App\Models\Vendor;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.super-admin')] class extends Component {
    public $verifyingVendorId = null;

    public ?string $verifyingVendorName = null;

    #[On('openVerifyConfirm')]
    public function openVerifyConfirm(int|string $id, ?string $name = null): void
    {
        $this->verifyingVendorId = $id;
        $this->verifyingVendorName = $name ?: (Vendor::query()->find($id)?->name ?? 'Mitra');
        Flux::modal('confirm-verification')->show();
    }

    public function confirmVerification(): void
    {
        if ($this->verifyingVendorId) {
            $vendor = Vendor::query()->find($this->verifyingVendorId);

            if ($vendor) {
                $vendor->update([
                    'status_verification' => VendorVerificationStatus::Approved,
                ]);

                Flux::toast(SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
            }

            $this->dispatch('pg:eventRefresh-VendorVerificationsTable');
            $this->reset('verifyingVendorId', 'verifyingVendorName');
            Flux::modal('confirm-verification')->close();
        }
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Verifikasi Mitra</flux:heading>
        <flux:subheading>Kelola antrean dan verifikasi mitra baru yang mendaftar.</flux:subheading>
    </div>

    <flux:card>
        <livewire:super-admin.datatables.vendor-verifications-table />
    </flux:card>

    <!-- Verification Confirmation Modal -->
    <flux:modal name="confirm-verification" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Verifikasi Mitra?</flux:heading>
                <flux:text class="mt-2">
                    Anda akan menyetujui dan memverifikasi mitra <strong class="text-zinc-900 dark:text-white">{{ $verifyingVendorName }}</strong>. <br>
                    Mitra akan aktif dan dapat mulai beroperasi di sistem PawPaw.
                </flux:text>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="confirmVerification" variant="primary">
                    <span wire:loading.remove wire:target="confirmVerification">Ya, Verifikasi</span>
                    <span wire:loading wire:target="confirmVerification">Memproses...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
