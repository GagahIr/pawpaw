@props(['message' => '', 'type' => 'success'])

<div x-data="{
    visible: false,
    message: '',
    type: 'success',
    timer: null,
    show(message, type = 'success') {
        clearTimeout(this.timer);
        this.message = message;
        this.type = type;
        this.visible = true;
        this.timer = setTimeout(() => { this.visible = false }, 3000);
    },
    destroy() { clearTimeout(this.timer) },
}" x-init="if (@js($message)) show(@js($message), @js($type))"
    x-on:client-registration-notification.window="show($event.detail.message, $event.detail.type)" x-show="visible"
    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none"
    class="fixed top-6 left-1/2 z-[100] flex w-[calc(100%-2rem)] max-w-md -translate-x-1/2 items-start gap-3 rounded-xl border p-4 shadow-lg"
    x-bind:class="type === 'success'
        ?
        'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200' :
        'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200'"
    role="status" aria-live="polite" aria-atomic="true">
    <span x-show="type === 'success'" aria-hidden="true"><flux:icon.check-circle class="size-5" /></span>
    <span x-show="type !== 'success'" aria-hidden="true"><flux:icon.exclamation-circle class="size-5" /></span>
    <p x-text="message" class="min-w-0 flex-1 text-sm leading-relaxed"></p>
    <button type="button" x-on:click="visible = false; clearTimeout(timer)" aria-label="Tutup notifikasi"
        class="shrink-0 rounded p-0.5 opacity-70 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2">
        <flux:icon.x-mark variant="micro" />
    </button>
</div>
