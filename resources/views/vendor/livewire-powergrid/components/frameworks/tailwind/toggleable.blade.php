@php
    $value = (int) $row->{data_get($column, 'field')};

    $trueValue = data_get($column, 'toggleable')['default'][0] ?? 'Yes';
    $falseValue = data_get($column, 'toggleable')['default'][1] ?? 'No';
@endphp

<div class="flex flex-row justify-center">
    @if ($showToggleable)
        @php
            $rowId = data_get($row, $this->realPrimaryKey);
            $rowName = (string) (data_get($row, 'name') ?? 'Mitra');
            $dataField = data_get($column, 'dataField') ?: data_get($column, 'field');
        @endphp
        <div
            x-data="{
                toggle: @js($value),
                handleClick() {
                    const field = @js($dataField);
                    if (field === 'status_verification' || field === 'is_verified') {
                        if (!this.toggle) {
                            $wire.dispatch('openVerifyConfirm', { id: @js($rowId), name: @js($rowName) });
                            return;
                        }
                    }

                    this.toggle = this.toggle ? 0 : 1;

                    $wire.dispatch('pg:toggleable-{{ $tableName }}', {
                        id: @js($rowId),
                        field: field,
                        value: this.toggle
                    });
                }
            }"
            class="flex items-center justify-center"
        >
            <button
                type="button"
                role="switch"
                :aria-checked="toggle ? 'true' : 'false'"
                x-on:click="handleClick"
                :class="toggle
                    ? 'bg-emerald-600 dark:bg-emerald-500 ring-2 ring-emerald-600/20'
                    : 'bg-zinc-300 dark:bg-zinc-600 hover:bg-zinc-400 dark:hover:bg-zinc-500 ring-1 ring-zinc-400/40'"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 shadow-sm"
                title="{{ $value ? $trueValue : $falseValue }}"
            >
                <span class="sr-only">{{ $value ? $trueValue : $falseValue }}</span>
                <span
                    :class="toggle ? 'translate-x-5' : 'translate-x-0'"
                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
                ></span>
            </button>
        </div>
    @else
        <div @class([
            'text-xs px-3 py-1 text-center rounded-full font-medium',
            'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $value === 0,
            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' => $value === 1,
        ])>
            {{ $value === 0 ? $falseValue : $trueValue }}
        </div>
    @endif
</div>
