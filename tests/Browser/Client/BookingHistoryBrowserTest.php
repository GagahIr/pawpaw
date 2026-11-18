<?php

namespace Tests\Browser\Client;

use App\Models\BookingClinic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Concerns\CreatesClientBookingData;
use Tests\DuskTestCase;

class BookingHistoryBrowserTest extends DuskTestCase
{
    use CreatesClientBookingData;
    use DatabaseTruncation;

    private User $client;

    private BookingClinic $upcoming;

    private const HISTORY_TAB = 'div[role="tablist"] button:nth-child(2)';

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createClientUser();
        $vendor = $this->createVendor(['name' => 'Klinik Sehat Hewan']);
        $service = $this->addService($vendor, 'Clinic', [
            ['name' => 'Vaksinasi Rabies', 'price' => 150000, 'duration' => 60, 'capacity' => 1],
            ['name' => 'Cek Darah', 'price' => 120000, 'duration' => 30, 'capacity' => 1],
            ['name' => 'Rontgen', 'price' => 200000, 'duration' => 30, 'capacity' => 1],
        ]);

        $item = fn (string $name) => $service->vendorServiceItems->firstWhere('name', $name);

        $this->upcoming = $this->createAppointment($this->client, $item('Vaksinasi Rabies'), now()->addDays(3), 600, 'pending');
        $this->createAppointment($this->client, $item('Cek Darah'), now()->subDays(5), 600, 'completed');
        $this->createAppointment($this->client, $item('Rontgen'), now()->subDays(4), 630, 'cancelled');
    }

    public function test_reservasi_saya_opens_on_the_active_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.my-bookings'))
                ->waitForText('Reservasi saya')
                ->assertSee('Vaksinasi Rabies')
                ->assertDontSee('Cek Darah')
                ->assertDontSee('Rontgen');
        });
    }

    public function test_client_can_switch_to_the_riwayat_tab_and_filter_by_status(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.my-bookings'))
                ->waitForText('Vaksinasi Rabies')
                ->click(self::HISTORY_TAB)
                ->waitForText('Cek Darah')
                ->assertSee('Rontgen')
                ->assertDontSee('Vaksinasi Rabies')
                ->press('Dibatalkan')
                ->waitUntilMissingText('Cek Darah')
                ->assertSee('Rontgen')
                ->press('Selesai')
                ->waitUntilMissingText('Rontgen')
                ->assertSee('Cek Darah');
        });
    }

    public function test_client_can_search_the_reservations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.my-bookings'))
                ->waitForText('Vaksinasi Rabies')
                ->type('input[type="search"]', 'tidak ada yang cocok')
                ->waitForText('Tidak ada reservasi yang cocok')
                ->press('Reset filter')
                ->waitForText('Vaksinasi Rabies');
        });
    }

    public function test_client_can_cancel_a_pending_reservation_and_find_it_in_the_history(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.my-bookings'))
                ->waitForText('Vaksinasi Rabies')
                ->press('Batalkan')
                ->waitForText('Batalkan reservasi?')
                ->press('Ya, batalkan')
                ->waitUntilMissingText('Vaksinasi Rabies')
                ->click(self::HISTORY_TAB)
                ->waitForText('Vaksinasi Rabies');
        });

        $this->assertSame('cancelled', BookingClinic::find($this->upcoming->id)->status);
    }

    public function test_riwayat_menu_lists_finished_and_cancelled_reservations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.history'))
                ->waitForText('Riwayat Reservasi')
                ->assertSee('Cek Darah')
                ->assertSee('Rontgen')
                ->assertSee('Selesai')
                ->assertSee('Dibatalkan')
                ->assertDontSee('Vaksinasi Rabies');
        });
    }

    public function test_riwayat_menu_is_empty_for_a_new_client(): void
    {
        $newcomer = $this->createClientUser(['email' => 'baru@example.test']);

        $this->browse(function (Browser $browser) use ($newcomer) {
            $browser->loginAs($newcomer, 'client')
                ->visit(route('client.history'))
                ->waitForText('Belum ada riwayat reservasi')
                ->clickLink('Cari Klinik')
                ->waitForText('Klinik yang Tersedia')
                ->assertRouteIs('client.clinic');
        });
    }
}
