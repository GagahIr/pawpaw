<?php

use App\Actions\Client\Booking\GetHistoryBookingsAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesClientBookingData;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, CreatesClientBookingData::class);

/*
 * Waktu dibekukan pada Senin 16 Nov 2026 pukul 08:00.
 *
 * Riwayat : Cek Darah (completed, 10 Nov), Rontgen (cancelled, 11 Nov),
 *           Potong Kuku (rejected, grooming, 12 Nov), Pet Hotel Kucing (completed, 5 sampai 7 Nov)
 * Bukan riwayat : Vaksinasi Rabies (pending), Konsultasi Umum (confirmed)
 */
beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    Carbon::setTestNow('2026-11-16 08:00:00');

    $this->client = $this->createClientUser();
    $clinic = $this->createVendor(['name' => 'Klinik Sehat Hewan']);
    $salon = $this->createVendor(['name' => 'Salon Bulu Cantik']);
    $hotel = $this->createVendor(['name' => 'Hotel Satwa Nyaman']);

    $clinicService = $this->addService($clinic, 'Clinic', [
        ['name' => 'Cek Darah', 'price' => 120000, 'duration' => 30, 'capacity' => 1],
        ['name' => 'Rontgen', 'price' => 200000, 'duration' => 30, 'capacity' => 1],
        ['name' => 'Vaksinasi Rabies', 'price' => 150000, 'duration' => 60, 'capacity' => 1],
        ['name' => 'Konsultasi Umum', 'price' => 80000, 'duration' => 30, 'capacity' => 1],
    ]);
    $groomingService = $this->addService($salon, 'Grooming', [
        ['name' => 'Potong Kuku', 'price' => 30000, 'duration' => 30, 'capacity' => 1],
    ]);
    $hotelService = $this->addService($hotel, 'Pet Hotel', [
        ['name' => 'Pet Hotel Kucing', 'price' => 75000, 'duration' => 1440, 'capacity' => 1],
    ]);

    $item = fn ($service, string $name) => $service->vendorServiceItems->firstWhere('name', $name);
    $this->bloodTest = $item($clinicService, 'Cek Darah');
    $this->xray = $item($clinicService, 'Rontgen');
    $this->rabies = $item($clinicService, 'Vaksinasi Rabies');
    $this->consultation = $item($clinicService, 'Konsultasi Umum');
    $this->nailTrim = $item($groomingService, 'Potong Kuku');
    $this->hotelStay = $item($hotelService, 'Pet Hotel Kucing');

    $this->createAppointment($this->client, $this->bloodTest, Carbon::parse('2026-11-10'), 600, 'completed');
    $this->createAppointment($this->client, $this->xray, Carbon::parse('2026-11-11'), 630, 'cancelled');
    $this->createAppointment($this->client, $this->nailTrim, Carbon::parse('2026-11-12'), 540, 'rejected', 'grooming');
    $this->createPetHotelBooking($this->client, $this->hotelStay, Carbon::parse('2026-11-05'), Carbon::parse('2026-11-07'), 'completed');

    $this->createAppointment($this->client, $this->rabies, Carbon::parse('2026-11-18'), 600, 'pending');
    $this->createAppointment($this->client, $this->consultation, Carbon::parse('2026-11-19'), 660, 'confirmed');
});

afterEach(function () {
    Carbon::setTestNow();
});

/*
|--------------------------------------------------------------------------
| HTTP: akses halaman riwayat
|--------------------------------------------------------------------------
*/

it('redirects guests to the client login page', function () {
    $this->get(route('client.history'))->assertRedirect(route('client.login'));
});

it('renders the history page and links to it from the menu', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSee('Riwayat Reservasi')
        ->assertSee('Daftar booking yang telah selesai atau dibatalkan')
        ->assertSee('href="'.route('client.history').'"', false)
        ->assertSee('href="'.route('client.my-bookings').'"', false);
});

it('lists completed, cancelled and rejected bookings of every service type', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSee('Cek Darah')
        ->assertSee('Rontgen')
        ->assertSee('Potong Kuku')
        ->assertSee('Pet Hotel Kucing');
});

it('leaves out bookings that are still pending or confirmed', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertDontSee('Vaksinasi Rabies')
        ->assertDontSee('Konsultasi Umum');
});

it('leaves out pending bookings that expired', function () {
    $this->createAppointment($this->client, $this->xray, Carbon::parse('2026-11-14'), 600, 'pending', createdAt: Carbon::parse('2026-11-13 09:00'));

    expect(app(GetHistoryBookingsAction::class)->execute($this->client->userClient->id))->toHaveCount(4);
});

it('shows the status label, service category, vendor and price on each card', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSee('Selesai')
        ->assertSee('Dibatalkan')
        ->assertSee('Ditolak')
        ->assertSee('Clinic')
        ->assertSee('Grooming')
        ->assertSee('Pet Hotel')
        ->assertSee('Klinik Sehat Hewan')
        ->assertSee('Salon Bulu Cantik')
        ->assertSee('Hotel Satwa Nyaman')
        ->assertSee('Rp 120.000')
        ->assertSee('Rp 200.000')
        ->assertSee('Rp 30.000')
        ->assertSee('Rp 75.000');
});

it('shows the booking date with the start time, and the stay range for the pet hotel', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSee('10 Nov 2026, 10:00')
        ->assertSee('11 Nov 2026, 10:30')
        ->assertSee('12 Nov 2026, 09:00')
        ->assertSee('05 Nov 2026')
        ->assertSee('07 Nov 2026');
});

it('sorts the history from the most recent schedule to the oldest', function () {
    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSeeInOrder(['Potong Kuku', 'Rontgen', 'Cek Darah', 'Pet Hotel Kucing']);
});

it('only shows the history of the signed in client', function () {
    $other = $this->createClientUser(['email' => 'lain@example.test']);
    $this->createAppointment($other, $this->consultation, Carbon::parse('2026-11-09'), 600, 'completed');

    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertDontSee('Konsultasi Umum');

    $this->actingAs($other, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSee('Konsultasi Umum')
        ->assertDontSee('Cek Darah');
});

it('shows an empty state with a link to find clinics when there is no history', function () {
    $newcomer = $this->createClientUser(['email' => 'baru@example.test']);

    $this->actingAs($newcomer, 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSee('Belum ada riwayat reservasi')
        ->assertSee('Cari Klinik')
        ->assertSee('href="'.route('client.clinic').'"', false);
});

it('shows the empty state for an account that has no client profile yet', function () {
    $newcomer = $this->createClientUser(['email' => 'baru@example.test']);
    $newcomer->userClient()->delete();

    $this->actingAs($newcomer->fresh(), 'client')->withoutVite()->get(route('client.history'))
        ->assertOk()
        ->assertSee('Belum ada riwayat reservasi');
});

it('renders the history inside the Livewire component too', function () {
    Livewire::actingAs($this->client, 'client')
        ->test('client.history.history')
        ->assertSee('Cek Darah')
        ->assertDontSee('Vaksinasi Rabies');
});

/*
|--------------------------------------------------------------------------
| GetHistoryBookingsAction
|--------------------------------------------------------------------------
*/

it('normalizes each history item for the view', function () {
    $items = app(GetHistoryBookingsAction::class)->execute($this->client->userClient->id);

    expect($items)->toHaveCount(4);

    $first = $items->first();

    expect($first)->toMatchArray([
        'type' => 'grooming',
        'category_label' => 'Grooming',
        'vendor_name' => 'Salon Bulu Cantik',
        'service_item_name' => 'Potong Kuku',
        'price' => 30000,
        'status' => 'rejected',
        'date_label' => '12 Nov 2026, 09:00',
    ]);
});

it('returns an empty collection for a client without bookings', function () {
    $newcomer = $this->createClientUser(['email' => 'baru@example.test']);

    expect(app(GetHistoryBookingsAction::class)->execute($newcomer->userClient->id))->toBeEmpty();
});
