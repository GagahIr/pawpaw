<?php

namespace Tests\Browser\Client;

use App\Models\BookingClinic;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorServiceItem;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Concerns\CreatesClientBookingData;
use Tests\DuskTestCase;

/**
 * Browser berjalan dengan jam sungguhan, jadi tes memakai besok sebagai tanggal kunjungan
 * supaya slot jam 10:00 selalu tersedia kapan pun tes dijalankan.
 */
class ClinicReservationBrowserTest extends DuskTestCase
{
    use CreatesClientBookingData;
    use DatabaseMigrations;

    private User $client;

    private Vendor $vendor;

    private VendorServiceItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createClientUser();
        $this->vendor = $this->createVendor(['name' => 'Klinik Sehat Hewan']);
        $this->openEveryDay($this->vendor);

        $service = $this->addService($this->vendor, 'Clinic', [
            ['name' => 'Vaksinasi Rabies', 'price' => 150000, 'duration' => 60, 'capacity' => 1],
        ]);
        $this->item = $service->vendorServiceItems->first();
    }

    private function tomorrow(): string
    {
        return now()->addDay()->toDateString();
    }

    private function dateButton(string $date): string
    {
        return "button[wire\\:click=\"selectDate('{$date}')\"]";
    }

    public function test_reservation_page_asks_for_the_next_step_until_everything_is_chosen(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic.booking', $this->vendor->slug))
                ->waitForText('Buat reservasi')
                ->assertSee('Klinik Sehat Hewan')
                ->assertSee('Pilih jenis perawatan')
                ->click('button[role="radio"]')
                ->waitForText('Pilih jam kunjungan untuk melanjutkan')
                ->click($this->dateButton($this->tomorrow()))
                ->waitFor('button[aria-label="Pilih jam 10:00"]')
                ->click('button[aria-label="Pilih jam 10:00"]')
                ->waitForText('Estimasi biaya')
                ->assertSee('150.000')
                ->assertDontSee('Pilih jam kunjungan untuk melanjutkan');
        });
    }

    public function test_client_can_reserve_a_clinic_visit_from_the_clinic_detail_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.vendor.detail', $this->vendor->slug))
                ->waitForText('Reservasi Sekarang')
                ->clickLink('Reservasi Sekarang')
                ->waitForText('Buat reservasi')
                ->click('button[role="radio"]')
                ->waitFor($this->dateButton($this->tomorrow()))
                ->click($this->dateButton($this->tomorrow()))
                ->waitFor('button[aria-label="Pilih jam 10:00"]')
                ->click('button[aria-label="Pilih jam 10:00"]')
                ->waitForText('Estimasi biaya')
                ->press('Lanjut pesan')
                ->waitForText('Total tagihan')
                ->click('button[wire\:click="reserve"]')
                ->waitForLocation('/clinic/'.$this->vendor->slug)
                ->assertPathIs('/clinic/'.$this->vendor->slug);
        });

        $booking = BookingClinic::sole();

        $this->assertSame('pending', $booking->status);
        $this->assertSame(600, $booking->time_slot);
        $this->assertSame($this->tomorrow(), $booking->booking_date->toDateString());
        $this->assertSame($this->item->id, $booking->vendor_service_item_id);
    }

    public function test_reserved_visit_shows_up_under_reservasi_saya(): void
    {
        $this->createAppointment($this->client, $this->item, now()->addDays(2), 600, 'pending');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.my-bookings'))
                ->waitForText('Vaksinasi Rabies')
                ->assertSee('Menunggu konfirmasi')
                ->assertSee('Klinik Sehat Hewan')
                ->assertSee('150.000');
        });
    }

    public function test_client_cannot_open_the_reservation_page_of_an_unverified_clinic(): void
    {
        $hidden = $this->createVendor(['name' => 'Klinik Belum Aktif', 'status_verification' => 'pending']);
        $this->addService($hidden);

        $this->browse(function (Browser $browser) use ($hidden) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic.booking', $hidden->slug))
                ->assertDontSee('Buat reservasi');
        });
    }
}
