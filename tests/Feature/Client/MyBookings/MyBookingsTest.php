<?php

use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesClientBookingData;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, CreatesClientBookingData::class);

/*
 * Waktu dibekukan pada Senin 16 Nov 2026 pukul 08:00.
 *
 * Aktif   : Vaksinasi Rabies (18 Nov, pending), Konsultasi Umum (19 Nov, confirmed),
 *           Mandi Kutu (20 Nov, pending)
 * Riwayat : Cek Darah (completed), Rontgen (cancelled), Potong Kuku (rejected),
 *           Steril Kucing (pending yang jadwalnya sudah lewat, tampil sebagai kedaluwarsa)
 */
beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    Carbon::setTestNow('2026-11-16 08:00:00');

    $this->client = $this->createClientUser();
    $this->clinic = $this->createVendor(['name' => 'Klinik Sehat Hewan']);
    $this->salon = $this->createVendor(['name' => 'Salon Bulu Cantik']);

    $clinicService = $this->addService($this->clinic, 'Clinic', [
        ['name' => 'Vaksinasi Rabies', 'price' => 150000, 'duration' => 60, 'capacity' => 1],
        ['name' => 'Konsultasi Umum', 'price' => 80000, 'duration' => 30, 'capacity' => 1],
        ['name' => 'Cek Darah', 'price' => 120000, 'duration' => 30, 'capacity' => 1],
        ['name' => 'Rontgen', 'price' => 200000, 'duration' => 30, 'capacity' => 1],
        ['name' => 'Steril Kucing', 'price' => 500000, 'duration' => 60, 'capacity' => 1],
    ]);
    $groomingService = $this->addService($this->salon, 'Grooming', [
        ['name' => 'Mandi Kutu', 'price' => 90000, 'duration' => 60, 'capacity' => 1],
        ['name' => 'Potong Kuku', 'price' => 30000, 'duration' => 30, 'capacity' => 1],
    ]);

    $item = fn ($service, string $name) => $service->vendorServiceItems->firstWhere('name', $name);
    $this->rabies = $item($clinicService, 'Vaksinasi Rabies');
    $this->consultation = $item($clinicService, 'Konsultasi Umum');
    $this->bloodTest = $item($clinicService, 'Cek Darah');
    $this->xray = $item($clinicService, 'Rontgen');
    $this->sterilization = $item($clinicService, 'Steril Kucing');
    $this->bath = $item($groomingService, 'Mandi Kutu');
    $this->nailTrim = $item($groomingService, 'Potong Kuku');

    $this->rabiesBooking = $this->createAppointment($this->client, $this->rabies, Carbon::parse('2026-11-18'), 600, 'pending', createdAt: Carbon::parse('2026-11-16 06:00'));
    $this->consultationBooking = $this->createAppointment($this->client, $this->consultation, Carbon::parse('2026-11-19'), 660, 'confirmed', createdAt: Carbon::parse('2026-11-16 07:00'));
    $this->bathBooking = $this->createAppointment($this->client, $this->bath, Carbon::parse('2026-11-20'), 540, 'pending', 'grooming', Carbon::parse('2026-11-16 07:30'));

    $this->createAppointment($this->client, $this->bloodTest, Carbon::parse('2026-11-10'), 600, 'completed');
    $this->createAppointment($this->client, $this->xray, Carbon::parse('2026-11-11'), 600, 'cancelled');
    $this->createAppointment($this->client, $this->nailTrim, Carbon::parse('2026-11-12'), 600, 'rejected', 'grooming');
    $this->createAppointment($this->client, $this->sterilization, Carbon::parse('2026-11-14'), 600, 'pending', createdAt: Carbon::parse('2026-11-13 09:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function myBookings(object $test, ?object $user = null)
{
    return Livewire::actingAs($user ?? $test->client, 'client')->test('client.my_bookings.my-bookings');
}

/*
|--------------------------------------------------------------------------
| HTTP: akses halaman dan tab Aktif
|--------------------------------------------------------------------------
*/

it('redirects guests to the client login page', function () {
    $this->get(route('client.my-bookings'))->assertRedirect(route('client.login'));
});

it('renders the page with the Aktif and Riwayat tabs', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.my-bookings'))
        ->assertOk()
        ->assertSee('Reservasi saya')
        ->assertSee('Pantau jadwal dan status reservasi Anda')
        ->assertSeeInOrder(['Aktif', 'Riwayat']);
});

it('opens on the active tab and lists pending and confirmed bookings only', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.my-bookings'))
        ->assertOk()
        ->assertSee('Vaksinasi Rabies')
        ->assertSee('Konsultasi Umum')
        ->assertSee('Mandi Kutu')
        ->assertDontSee('Cek Darah')
        ->assertDontSee('Rontgen')
        ->assertDontSee('Potong Kuku')
        ->assertDontSee('Steril Kucing');
});

it('shows status, vendor, schedule and price on a booking card', function () {
    myBookings($this)
        ->assertSee('Menunggu konfirmasi')
        ->assertSee('Datang 10 menit lebih awal ke')
        ->assertSee('Klinik Sehat Hewan')
        ->assertSee('Salon Bulu Cantik')
        ->assertSee('18 Nov 2026')
        ->assertSee('Pukul 10:00 – 11:00')
        ->assertSee('150.000');
});

it('only shows the bookings of the signed in client', function () {
    $other = $this->createClientUser(['email' => 'lain@example.test']);
    $this->createAppointment($other, $this->bloodTest, Carbon::parse('2026-11-21'), 600, 'pending');

    myBookings($this)->assertDontSee('Cek Darah');
    myBookings($this, $other)
        ->assertSee('Cek Darah')
        ->assertDontSee('Vaksinasi Rabies');
});

it('shows an empty state with a link to find services when there is no active booking', function () {
    $newcomer = $this->createClientUser(['email' => 'baru@example.test']);

    myBookings($this, $newcomer)
        ->assertSee('Belum ada reservasi aktif')
        ->assertSee('Cari layanan')
        ->assertSee('href="'.route('client.clinic').'"', false);
});

it('does not list a pending booking whose schedule has passed as active', function () {
    myBookings($this)->assertDontSee('Steril Kucing');
});

/*
|--------------------------------------------------------------------------
| Tab Riwayat
|--------------------------------------------------------------------------
*/

it('lists finished, cancelled, rejected and expired bookings on the history tab', function () {
    myBookings($this)
        ->set('tab', 'history')
        ->assertSee('Cek Darah')
        ->assertSee('Rontgen')
        ->assertSee('Potong Kuku')
        ->assertSee('Ditolak klinik')
        ->assertSee('Steril Kucing')
        ->assertSee('belum sempat mengonfirmasi')
        ->assertDontSee('Vaksinasi Rabies')
        ->assertDontSee('Konsultasi Umum')
        ->assertDontSee('Mandi Kutu');
});

it('treats a pending booking older than 24 hours as expired', function () {
    $this->createAppointment($this->client, $this->bloodTest, Carbon::parse('2026-11-25'), 600, 'pending', createdAt: Carbon::parse('2026-11-15 07:00'));

    myBookings($this)
        ->set('search', 'Cek Darah')
        ->assertDontSee('Menunggu konfirmasi')
        ->set('tab', 'history')
        ->assertSee('belum sempat mengonfirmasi');
});

it('shows an empty history state for a client without past bookings', function () {
    $newcomer = $this->createClientUser(['email' => 'baru@example.test']);

    myBookings($this, $newcomer)
        ->set('tab', 'history')
        ->assertSee('Belum ada riwayat');
});

it('resets the status filter when switching tabs', function () {
    myBookings($this)
        ->set('status', 'confirmed')
        ->set('tab', 'history')
        ->assertSet('status', '');
});

/*
|--------------------------------------------------------------------------
| Filter dan pencarian
|--------------------------------------------------------------------------
*/

it('filters active bookings by status', function () {
    myBookings($this)
        ->set('status', 'confirmed')
        ->assertSee('Konsultasi Umum')
        ->assertDontSee('Vaksinasi Rabies')
        ->assertDontSee('Mandi Kutu')
        ->set('status', 'pending')
        ->assertSee('Vaksinasi Rabies')
        ->assertSee('Mandi Kutu')
        ->assertDontSee('Konsultasi Umum');
});

it('filters the history by status', function () {
    myBookings($this)
        ->set('tab', 'history')
        ->set('status', 'cancelled')
        ->assertSee('Rontgen')
        ->assertDontSee('Cek Darah')
        ->assertDontSee('Potong Kuku')
        ->set('status', 'expired')
        ->assertSee('Steril Kucing')
        ->assertDontSee('Rontgen');
});

it('ignores a status that does not belong to the tab', function () {
    myBookings($this)
        ->set('status', 'completed')
        ->assertSee('Vaksinasi Rabies')
        ->assertSee('Konsultasi Umum')
        ->assertSee('Mandi Kutu');
});

it('searches bookings by vendor name', function () {
    myBookings($this)
        ->set('search', 'Salon')
        ->assertSee('Mandi Kutu')
        ->assertDontSee('Vaksinasi Rabies')
        ->assertDontSee('Konsultasi Umum');
});

it('searches bookings by treatment name', function () {
    myBookings($this)
        ->set('search', 'konsultasi')
        ->assertSee('Konsultasi Umum')
        ->assertDontSee('Vaksinasi Rabies')
        ->assertDontSee('Mandi Kutu');
});

it('filters bookings by service type', function () {
    myBookings($this)
        ->set('type', 'grooming')
        ->assertSee('Mandi Kutu')
        ->assertDontSee('Vaksinasi Rabies')
        ->set('type', 'clinic')
        ->assertSee('Vaksinasi Rabies')
        ->assertDontSee('Mandi Kutu');
});

it('filters bookings by schedule date range', function () {
    myBookings($this)
        ->set('dateFrom', '2026-11-19')
        ->assertDontSee('Vaksinasi Rabies')
        ->assertSee('Konsultasi Umum')
        ->assertSee('Mandi Kutu')
        ->set('dateTo', '2026-11-19')
        ->assertSee('Konsultasi Umum')
        ->assertDontSee('Mandi Kutu');
});

it('swaps a reversed date range instead of returning nothing', function () {
    myBookings($this)
        ->set('dateFrom', '2026-11-20')
        ->set('dateTo', '2026-11-18')
        ->assertSee('Vaksinasi Rabies')
        ->assertSee('Mandi Kutu');
});

it('sorts by the newest reservation made by default', function () {
    myBookings($this)->assertSeeInOrder(['Mandi Kutu', 'Konsultasi Umum', 'Vaksinasi Rabies']);
});

it('sorts by the nearest and the furthest schedule', function () {
    myBookings($this)
        ->set('sort', 'soonest')
        ->assertSeeInOrder(['Vaksinasi Rabies', 'Konsultasi Umum', 'Mandi Kutu'])
        ->set('sort', 'latest')
        ->assertSeeInOrder(['Mandi Kutu', 'Konsultasi Umum', 'Vaksinasi Rabies']);
});

it('shows a no-match state and resets every filter in one click', function () {
    myBookings($this)
        ->set('search', 'tidak-ada-yang-cocok')
        ->assertSee('Tidak ada reservasi yang cocok')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('status', '')
        ->assertSet('type', '')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSet('sort', 'newest')
        ->assertSee('Vaksinasi Rabies')
        ->assertDontSee('Tidak ada reservasi yang cocok');
});

/*
|--------------------------------------------------------------------------
| Tampilkan lebih banyak
|--------------------------------------------------------------------------
*/

it('loads ten bookings at a time and reveals more on demand', function () {
    foreach (range(1, 9) as $day) {
        $this->createAppointment($this->client, $this->consultation, Carbon::parse('2026-12-01')->addDays($day), 600, 'pending');
    }

    $page = myBookings($this);

    expect(preg_match_all('/#(KLN|GRM|HTL)-\d{4}/', $page->html()))->toBe(10);
    $page->assertSee('Tampilkan lebih banyak');

    $page->call('loadMore')->assertSet('limit', 20);

    expect(preg_match_all('/#(KLN|GRM|HTL)-\d{4}/', $page->html()))->toBe(12);
    $page->assertDontSee('Tampilkan lebih banyak');
});

/*
|--------------------------------------------------------------------------
| Pembatalan reservasi
|--------------------------------------------------------------------------
*/

it('cancels a pending booking and moves it to the history', function () {
    myBookings($this)
        ->call('askCancel', 'clinic', $this->rabiesBooking->id)
        ->call('cancel')
        ->assertDontSee('Vaksinasi Rabies')
        ->set('tab', 'history')
        ->assertSee('Vaksinasi Rabies');

    expect($this->rabiesBooking->fresh()->status)->toBe('cancelled');
});

it('cancels a pending grooming booking', function () {
    myBookings($this)
        ->call('askCancel', 'grooming', $this->bathBooking->id)
        ->call('cancel');

    expect(BookingGrooming::find($this->bathBooking->id)->status)->toBe('cancelled');
});

it('does not cancel a booking that the vendor already confirmed', function () {
    myBookings($this)
        ->call('askCancel', 'clinic', $this->consultationBooking->id)
        ->call('cancel')
        ->assertSee('Konsultasi Umum');

    expect($this->consultationBooking->fresh()->status)->toBe('confirmed');
});

it('does not cancel a booking that belongs to someone else', function () {
    $other = $this->createClientUser(['email' => 'lain@example.test']);
    $foreign = $this->createAppointment($other, $this->bloodTest, Carbon::parse('2026-11-21'), 600, 'pending');

    myBookings($this)
        ->call('askCancel', 'clinic', $foreign->id)
        ->call('cancel');

    expect(BookingClinic::find($foreign->id)->status)->toBe('pending');
});

it('ignores an unknown booking type when cancelling', function () {
    myBookings($this)
        ->call('askCancel', 'spa', $this->rabiesBooking->id)
        ->call('cancel');

    expect($this->rabiesBooking->fresh()->status)->toBe('pending');
});
