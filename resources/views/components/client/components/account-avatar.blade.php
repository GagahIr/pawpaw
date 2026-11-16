@props(['user' => null])

@php
    $avatarUrl = $user?->avatar ? asset('storage/' . $user->avatar) : '';
    $displayName = trim($user?->name ?? '') ?: (trim($user?->username ?? '') ?: 'PawPaw');
    $initials = collect(preg_split('/\s+/u', $displayName, -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $name): string => mb_strtoupper(mb_substr($name, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class('pawpaw-account-avatar-image') }} aria-hidden="true"
    x-data="{ avatarUrl: @js($avatarUrl) }"
    x-on:client-avatar-updated.window="avatarUrl = $event.detail.url || ''">
    <span>{{ $initials }}</span>
    <img @if ($avatarUrl) src="{{ $avatarUrl }}" @else style="display: none" @endif
        x-bind:src="avatarUrl || null" x-show="avatarUrl" x-on:error="avatarUrl = ''"
        x-init="if ($el.complete && !$el.naturalWidth) { avatarUrl = '' }"
        alt="" width="44" height="44">
</span>
