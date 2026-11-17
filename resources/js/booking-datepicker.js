import flatpickr from 'flatpickr';
const Indonesian = {
    weekdays: {
        shorthand: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
        longhand: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
    },
    months: {
        shorthand: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        longhand: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
    },
    firstDayOfWeek: 1,
    time_24hr: true,
    yearAriaLabel: 'Tahun',
    monthAriaLabel: 'Bulan',
    scrollTitle: 'Gulir untuk mengubah',
    toggleTitle: 'Klik untuk mengganti',
};

const datePickers = new Map();

function syncCalendarYearSelect(picker) {
    const select = picker.calendarContainer.querySelector('.pawpaw-calendar-year');
    if (!select) return;
    const thisYear = new Date().getFullYear();
    const firstYear = picker.config.minDate?.getFullYear() ?? Math.min(picker.currentYear, thisYear - 10);
    const lastYear = picker.config.maxDate?.getFullYear() ?? Math.max(picker.currentYear, thisYear + 5);
    const options = [];
    for (let year = firstYear; year <= lastYear; year++) {
        const option = document.createElement('option');
        option.value = String(year);
        option.textContent = String(year);
        options.push(option);
    }
    select.replaceChildren(...options);
    select.value = String(picker.currentYear);
}

function addCalendarYearSelect(picker) {
    const select = document.createElement('select');
    select.className = 'pawpaw-calendar-year';
    select.setAttribute('aria-label', 'Pilih tahun');
    select.addEventListener('change', () => {
        picker.changeYear(Number(select.value));
        syncCalendarYearSelect(picker);
    });
    picker.calendarContainer.querySelector('.flatpickr-current-month').append(select);
    syncCalendarYearSelect(picker);
}

function closedWeekdays(element) {
    return JSON.parse(element.dataset.closedWeekdays || '[]');
}

function updateDatePicker(element, picker) {
    const { min, max, value, model } = element.dataset;
    if (picker.config.minDate?.getTime() !== picker.parseDate(min, 'Y-m-d')?.getTime()) {
        picker.set('minDate', min || undefined);
    }
    if (picker.config.maxDate?.getTime() !== picker.parseDate(max, 'Y-m-d')?.getTime()) {
        picker.set('maxDate', max || undefined);
    }
    const weekdays = closedWeekdays(element);
    const closedDaysKey = JSON.stringify(weekdays);
    if (picker.pawpawClosedDaysKey !== closedDaysKey) {
        picker.set('disable', [(date) => weekdays.includes(date.getDay())]);
        picker.pawpawClosedDaysKey = closedDaysKey;
    }
    if (picker.input.value !== value) picker.setDate(value || [], false, 'Y-m-d');
    syncCalendarYearSelect(picker);
    if (picker.altInput) {
        picker.altInput.id = `booking-${model}-display`;
        picker.altInput.setAttribute('aria-labelledby', `booking-${model}-label`);
        element.querySelector('label').htmlFor = picker.altInput.id;
    }
}

function initBookingDatePickers() {
    datePickers.forEach((picker, element) => {
        if (element.isConnected) return;
        picker.destroy();
        datePickers.delete(element);
    });

    document.querySelectorAll('[data-booking-date-picker]').forEach((element) => {
        let picker = datePickers.get(element);
        if (!picker) {
            picker = flatpickr(element.querySelector('[data-picker-input]'), {
                locale: Indonesian,
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'j F Y',
                ariaDateFormat: 'l, j F Y',
                altInputClass: 'booking-date-display',
                disableMobile: true,
                monthSelectorType: 'dropdown',
                minDate: element.dataset.min || undefined,
                maxDate: element.dataset.max || undefined,
                defaultDate: element.dataset.value || undefined,
                disable: [(date) => closedWeekdays(element).includes(date.getDay())],
                onYearChange: (_, __, instance) => syncCalendarYearSelect(instance),
                onMonthChange: (_, __, instance) => syncCalendarYearSelect(instance),
                onReady: (_, __, instance) => {
                    instance.calendarContainer.classList.add('pawpaw-calendar');
                    instance.calendarContainer.setAttribute('role', 'dialog');
                    instance.calendarContainer.setAttribute('aria-label', element.dataset.label);
                    const caption = document.createElement('div');
                    caption.className = 'pawpaw-calendar-caption';
                    caption.textContent = element.dataset.label;
                    instance.calendarContainer.prepend(caption);
                    addCalendarYearSelect(instance);
                },
            });
            picker.pawpawClosedDaysKey = JSON.stringify(closedWeekdays(element));
            datePickers.set(element, picker);
        }
        updateDatePicker(element, picker);
    });
}

function destroyBookingDatePickers() {
    datePickers.forEach((picker) => picker.destroy());
    datePickers.clear();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBookingDatePickers, { once: true });
} else {
    initBookingDatePickers();
}

document.addEventListener('livewire:navigated', initBookingDatePickers);
document.addEventListener('livewire:navigating', destroyBookingDatePickers);

function registerBookingDatePickerHooks() {
    window.Livewire.hook('morphed', initBookingDatePickers);
}
if (window.Livewire) {
    registerBookingDatePickerHooks();
} else {
    document.addEventListener('livewire:init', registerBookingDatePickerHooks, { once: true });
}
