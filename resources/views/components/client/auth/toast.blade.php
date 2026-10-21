@php
    $errorMessage = session('client_login_error') ?? session('client_register_error');
    $message = $errorMessage
        ?? session('client_registered')
        ?? session('client_logged_in')
        ?? session('client_password_reset_success')
        ?? session('client_register_notice');
@endphp

<flux:toast position="top end" class="pt-4" />

@if ($message)
    <div x-data="{ message: @js($message), variant: @js($errorMessage ? 'danger' : 'success') }"
        x-init="$nextTick(() => $flux.toast({ text: message, variant: variant }))"></div>
@endif
