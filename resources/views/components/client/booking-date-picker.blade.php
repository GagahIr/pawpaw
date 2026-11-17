@props(['model', 'label', 'value' => '', 'min' => '', 'max' => '', 'closedWeekdays' => []])

<div class="booking-date-picker" data-booking-date-picker data-closed-weekdays="{{ json_encode($closedWeekdays) }}" data-model="{{ $model }}" data-value="{{ $value }}" data-min="{{ $min }}" data-max="{{ $max }}" data-label="{{ $label }}" wire:key="booking-date-picker-{{ $model }}">
    <label class="booking-date-label" id="booking-{{ $model }}-label" for="booking-{{ $model }}">{{ $label }}</label>
    <div class="booking-date-control" wire:ignore>
        <input id="booking-{{ $model }}" data-picker-input type="date" placeholder="Pilih tanggal" wire:model.live="{{ $model }}" value="{{ $value }}" min="{{ $min }}" max="{{ $max }}" aria-labelledby="booking-{{ $model }}-label">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="4"/><path d="M7 3v5m10-5v5M3 11h18m-13 4h2m4 0h2m-8 3h2"/></svg>
    </div>
</div>
