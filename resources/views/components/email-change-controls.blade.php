@props(['user', 'isEditing' => false, 'showPassword' => true, 'showNotice' => true])

<div class="{{ $showPassword ? 'mt-3 space-y-3' : 'mt-5' }}">
    @if ($showPassword && blank($user?->password))
        <p class="text-xs text-amber-800">
            Buat kata sandi PawPaw terlebih dahulu untuk mengganti email.
            <button type="button" wire:click="startChangingPassword" class="font-semibold underline">Buat kata sandi</button>
        </p>
    @elseif ($showPassword && ($isEditing || $user?->pending_email))
        <div>
            <label for="email-change-password" class="mb-2 block text-xs font-semibold text-zinc-600">Kata sandi saat ini</label>
            <input id="email-change-password" type="password" wire:model="emailChangePassword" autocomplete="current-password"
                class="w-full rounded-2xl border border-[#D9D0C5] bg-[#FCFAF6] px-4 py-3 text-sm focus:border-[#2D5945] focus:outline-none focus:ring-2 focus:ring-[#2D5945]/20"
                aria-describedby="email-change-password-help" />
            <p id="email-change-password-help" class="mt-1 text-xs text-zinc-500">Diperlukan hanya saat mengganti email atau mengirim ulang konfirmasi.</p>
            <flux:error name="emailChangePassword" class="mt-1" />
        </div>
    @endif

    @if ($showNotice && $user?->pending_email)
        @php
            $retryAt = $user->email_change_requested_at?->copy()->addSeconds(60) ?? now();
            $retryAfter = max(0, (int) ceil(now()->diffInSeconds($retryAt, false)));
        @endphp
        <div role="status" class="w-full rounded-xl bg-amber-50 p-4 text-sm text-amber-900 flex flex-wrap items-center gap-x-6 gap-y-3"
            wire:key="pending-email-{{ $user->id }}-{{ $user->email_change_requested_at?->format('U.u') }}"
            data-email-change-countdown="{{ $retryAfter }}"
            x-data="{
                remaining: {{ $retryAfter }},
                deadline: Date.now() + {{ $retryAfter }} * 1000,
                timer: null,
                init() {
                    if (this.remaining === 0) return;
                    this.timer = setInterval(() => {
                        this.remaining = Math.max(0, Math.ceil((this.deadline - Date.now()) / 1000));
                        if (this.remaining === 0) clearInterval(this.timer);
                    }, 1000);
                },
                destroy() { clearInterval(this.timer); }
            }">
            <div class="min-w-0 flex-[1_1_28rem]">
            <p>Menunggu konfirmasi ke <strong class="break-words">{{ $user->pending_email }}</strong>.</p>
            <p class="mt-1 text-xs">Email akun tetap {{ $user->email }}. Tautan berlaku 60 menit. Pengiriman ulang tersedia setelah 60 detik.</p>
            @if ($user->email_change_expires_at?->isPast())
                <p class="mt-1 text-xs font-semibold">Tautan sudah kedaluwarsa. Kirim ulang untuk mendapatkan tautan baru.</p>
            @endif
            </div>
            <div class="flex max-w-full shrink-0 flex-wrap items-center gap-4">
                <button type="button" wire:click="resendEmailChange" wire:loading.attr="disabled"
                    @disabled($retryAfter > 0) x-bind:disabled="remaining > 0"
                    class="whitespace-nowrap font-semibold underline disabled:cursor-not-allowed disabled:opacity-60"
                    x-text="remaining > 0 ? 'Kirim ulang (' + remaining + ' detik)' : 'Kirim ulang'">
                    {{ $retryAfter > 0 ? 'Kirim ulang ('.$retryAfter.' detik)' : 'Kirim ulang' }}
                </button>
                <button type="button" wire:click="cancelEmailChange" wire:loading.attr="disabled" class="whitespace-nowrap underline">Batalkan perubahan</button>
            </div>
        </div>
    @endif
</div>
