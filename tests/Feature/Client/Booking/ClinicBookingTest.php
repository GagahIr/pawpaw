<?php

use App\Actions\Client\Booking\BookingAction;
use App\Enums\DayOfWeek;
use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\BookingClinic;
use App\Models\Vendor;
use App\Models\VendorService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\CreatesClientBookingData;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, CreatesClientBookingData::class);

/*
 * Waktu dibekukan pada Senin 16 Nov 2026 pukul 08:00. Slot paling cepat yang boleh
 * dipesan adalah 09:00 (batas 60 menit ke depan), sehingga 10:00 = 600 menit tersedia
 * dan 08:00 = 480 menit sudah lewat.
 */
beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    Carbon::setTestNow('2026-11-16 08:00:00');

    $this->client = $this->createClientUser();
    $this->vendor = $this->createVendor(['name' => 'Klinik Sehat Hewan']);
    $this->openEveryDay($this->vendor);
    $this->service = $this->addService($this->vendor, 'Clinic', [
        ['name' => 'Vaksinasi', 'price' => 150000, 'duration' => 60, 'capacity' => 1],
        ['name' => 'Konsultasi', 'price' => 80000, 'duration' => 30, 'capacity' => 2],
    ]);
    $this->vaccination = $this->service->vendorServiceItems->firstWhere('name', 'Vaksinasi');
    $this->consultation = $this->service->vendorServiceItems->firstWhere('name', 'Konsultasi');
});

afterEach(function () {
    Carbon::setTestNow();
});

function bookingPage(object $test, ?Vendor $vendor = null)
{
    return Livewire::actingAs($test->client, 'client')
        ->test('client.booking.booking', ['slug' => ($vendor ?? $test->vendor)->slug]);
}

function clinicBookingAction(object $test, int $timeSlot, string $date = '2026-11-16', ?int $itemId = null, $user = null): BookingClinic
{
    return app(BookingAction::class)->execute(
        $user ?? $test->client,
        $test->vendor->slug,
        $test->service->id,
        $itemId ?? $test->vaccination->id,
        $date,
        $timeSlot,
    );
}

/*
|--------------------------------------------------------------------------
| HTTP: akses halaman reservasi
|--------------------------------------------------------------------------
*/

it('redirects guests to the client login page', function () {
    $this->get(route('client.clinic.booking', $this->vendor->slug))->assertRedirect(route('client.login'));
});

it('renders the reservation page for an eligible clinic', function () {
    $this->actingAs($this->client, 'client')->withoutVite()
        ->get(route('client.clinic.booking', $this->vendor->slug))
        ->assertOk()
        ->assertSee('Buat reservasi')
        ->assertSee('Klinik Sehat Hewan')
        ->assertSee('1. Pilih layanan');
});

it('links to the reservation page from the clinic detail page', function () {
    $this->actingAs($this->client, 'client')->withoutVite()
        ->get(route('client.vendor.detail', $this->vendor->slug))
        ->assertOk()
        ->assertSee('Reservasi Sekarang')
        ->assertSee('href="'.route('client.clinic.booking', $this->vendor->slug).'"', false);
});

it('returns 404 when the clinic cannot take reservations', function (array $attributes) {
    $vendor = $this->createVendor(['name' => 'Klinik Tidak Aktif'] + $attributes);
    $this->openEveryDay($vendor);
    $this->addService($vendor);

    $this->actingAs($this->client, 'client')->withoutVite()
        ->get(route('client.clinic.booking', $vendor->slug))
        ->assertNotFound();
})->with([
    'not verified' => [['status_verification' => VendorVerificationStatus::Pending]],
    'subscription inactive' => [['status_subscription' => VendorSubscriptionStatus::Inactive]],
    'subscription expired' => [['subscription_expired_at' => '2026-11-01 00:00:00']],
    'not joined yet' => [['join_at' => null]],
]);

it('returns 404 for an unknown clinic slug', function () {
    $this->actingAs($this->client, 'client')->withoutVite()
        ->get(route('client.clinic.booking', 'klinik-tidak-ada'))
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Langkah-langkah memilih layanan, perawatan, dan jam
|--------------------------------------------------------------------------
*/

it('selects the only service automatically and defaults the date to today', function () {
    bookingPage($this)
        ->assertSet('vendorServiceId', (string) $this->service->id)
        ->assertSet('bookingDate', '2026-11-16')
        ->assertSee('Vaksinasi')
        ->assertSee('Konsultasi')
        ->assertSee('Pilih jenis perawatan');
});

it('does not select a service automatically when the clinic offers several', function () {
    $this->addService($this->vendor, 'Grooming');

    bookingPage($this)
        ->assertSet('vendorServiceId', '')
        ->assertSee('Pilih kategori layanan untuk memulai');
});

it('shows the time slots of the chosen treatment and marks past slots as unavailable', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->assertSet('vendorServiceItemId', (string) $this->vaccination->id)
        ->assertSee('Pilih jam kunjungan untuk melanjutkan')
        ->assertSee('aria-label="Pilih jam 10:00"', false)
        ->assertSee('aria-label="Pilih jam 08:00, sudah lewat"', false);
});

it('moves to the next day and clears the chosen slot when the date changes', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->call('selectTime', 600)
        ->assertSet('timeSlot', '600')
        ->call('selectDate', '2026-11-17')
        ->assertSet('bookingDate', '2026-11-17')
        ->assertSet('timeSlot', '');
});

it('clears the chosen slot when another treatment is selected', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->call('selectTime', 600)
        ->call('selectItem', $this->consultation->id)
        ->assertSet('timeSlot', '');
});

it('tells the visitor the clinic is closed on a closed day', function () {
    $this->closeDay($this->vendor, DayOfWeek::Monday);

    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->assertSee('Vendor tutup pada tanggal ini.');
});

it('offers the next available day when today has no slot left', function () {
    Carbon::setTestNow('2026-11-16 19:30:00');

    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->assertSee('Jam operasional hari ini sudah lewat atau terlalu dekat.')
        ->call('useNextAvailable')
        ->assertSet('bookingDate', '2026-11-17');
});

/*
|--------------------------------------------------------------------------
| Konfirmasi dan pembuatan reservasi
|--------------------------------------------------------------------------
*/

it('creates a pending clinic booking for the chosen treatment, day and time', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->call('selectTime', 600)
        ->call('confirmReservation')
        ->assertHasNoErrors()
        ->call('reserve')
        ->assertHasNoErrors()
        ->assertRedirect(route('client.my-bookings'));

    $booking = BookingClinic::sole();

    expect($booking->status)->toBe('pending')
        ->and($booking->time_slot)->toBe(600)
        ->and($booking->booking_date->toDateString())->toBe('2026-11-16')
        ->and($booking->vendor_service_item_id)->toBe($this->vaccination->id)
        ->and($booking->user_client_id)->toBe($this->client->userClient->id);
});

it('can book a later day with the date strip', function () {
    bookingPage($this)
        ->call('selectItem', $this->consultation->id)
        ->call('selectDate', '2026-11-18')
        ->call('selectTime', 480)
        ->call('reserve')
        ->assertHasNoErrors();

    $booking = BookingClinic::sole();

    expect($booking->booking_date->toDateString())->toBe('2026-11-18')
        ->and($booking->time_slot)->toBe(480);
});

it('rejects a reservation when no time slot was chosen', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->call('confirmReservation')
        ->assertHasErrors(['timeSlot']);

    expect(BookingClinic::count())->toBe(0);
});

it('rejects a time slot that has already passed', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->call('selectTime', 480)
        ->call('reserve')
        ->assertHasErrors(['timeSlot']);

    expect(BookingClinic::count())->toBe(0);
});

it('rejects a booking date in the past', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->set('bookingDate', '2026-11-15')
        ->call('confirmReservation')
        ->assertHasErrors(['bookingDate']);
});

it('rejects a booking date more than 30 days ahead', function () {
    bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->set('bookingDate', '2026-12-17')
        ->call('confirmReservation')
        ->assertHasErrors(['bookingDate']);
});

it('requires a treatment before confirming', function () {
    bookingPage($this)
        ->call('confirmReservation')
        ->assertHasErrors(['vendorServiceItemId']);
});

it('shows an error and creates nothing when the slot was taken in the meantime', function () {
    $page = bookingPage($this)
        ->call('selectItem', $this->vaccination->id)
        ->call('selectTime', 600);

    clinicBookingAction($this, 600, user: $this->createClientUser(['email' => 'lain@example.test']));

    $page->call('reserve')->assertHasErrors(['time_slot'])->assertNoRedirect();

    expect(BookingClinic::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| BookingAction: aturan bisnis reservasi klinik
|--------------------------------------------------------------------------
*/

it('books a clinic slot through the action', function () {
    $booking = clinicBookingAction($this, 600);

    expect($booking)->toBeInstanceOf(BookingClinic::class)
        ->and($booking->status)->toBe('pending')
        ->and($booking->time_slot)->toBe(600);
});

it('refuses a slot that is already full', function () {
    clinicBookingAction($this, 600, user: $this->createClientUser(['email' => 'lain@example.test']));

    expect(fn () => clinicBookingAction($this, 600))
        ->toThrow(ValidationException::class, 'Slot sudah penuh.');
});

it('keeps a slot open while capacity remains', function () {
    clinicBookingAction($this, 600, itemId: $this->consultation->id, user: $this->createClientUser(['email' => 'lain@example.test']));

    $booking = clinicBookingAction($this, 600, itemId: $this->consultation->id);

    expect($booking->exists)->toBeTrue()
        ->and(BookingClinic::count())->toBe(2);
});

it('frees a slot again once the earlier booking was cancelled', function () {
    $other = $this->createClientUser(['email' => 'lain@example.test']);
    $this->createAppointment($other, $this->vaccination, Carbon::today(), 600, 'cancelled');

    expect(clinicBookingAction($this, 600)->exists)->toBeTrue();
});

it('refuses a booking on a day the clinic is closed', function () {
    $this->closeDay($this->vendor, DayOfWeek::Monday);

    expect(fn () => clinicBookingAction($this, 600))
        ->toThrow(ValidationException::class, 'Vendor tutup pada tanggal tersebut.');
});

it('refuses a slot outside the opening hours', function () {
    expect(fn () => clinicBookingAction($this, 19 * 60 + 30, '2026-11-17'))
        ->toThrow(ValidationException::class, 'Slot berada di luar jam operasional vendor.');
});

it('refuses a slot that already started today', function () {
    Carbon::setTestNow('2026-11-16 12:00:00');

    expect(fn () => clinicBookingAction($this, 600))
        ->toThrow(ValidationException::class, 'Slot waktu sudah lewat.');
});

it('refuses a treatment that belongs to another clinic', function () {
    $otherVendor = $this->createVendor(['name' => 'Klinik Lain']);
    $this->openEveryDay($otherVendor);
    $otherService = $this->addService($otherVendor);
    $otherItem = $otherService->vendorServiceItems->first();

    expect(fn () => app(BookingAction::class)->execute(
        $this->client,
        $this->vendor->slug,
        $otherService->id,
        $otherItem->id,
        '2026-11-17',
        600,
    ))->toThrow(ModelNotFoundException::class);
});

it('refuses a treatment of a service that is switched off', function () {
    VendorService::whereKey($this->service->id)->update(['status_service' => false]);

    expect(fn () => clinicBookingAction($this, 600))
        ->toThrow(ModelNotFoundException::class);
});
