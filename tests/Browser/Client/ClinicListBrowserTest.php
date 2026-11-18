<?php

namespace Tests\Browser\Client;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Concerns\CreatesClientBookingData;
use Tests\DuskTestCase;

class ClinicListBrowserTest extends DuskTestCase
{
    use CreatesClientBookingData;
    use DatabaseTruncation;

    private User $client;

    private const SEARCH_INPUT = 'input[placeholder="Cari klinik atau grooming..."]';

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createClientUser();

        $clinic = $this->createVendor(['name' => 'Klinik Sehat Hewan', 'address' => 'Jl. Ijen, Malang']);
        $this->addService($clinic, 'Clinic');

        $salon = $this->createVendor(['name' => 'Salon Bulu Cantik', 'address' => 'Jl. Veteran, Malang']);
        $this->addService($salon, 'Grooming');

        $this->createVendor(['name' => 'Klinik Belum Diverifikasi', 'status_verification' => 'pending']);
    }

    public function test_guest_is_sent_to_the_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('client.clinic'))
                ->assertRouteIs('client.login');
        });
    }

    public function test_client_sees_the_cards_of_listed_clinics_only(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic'))
                ->waitForText('Klinik yang Tersedia')
                ->assertSee('Klinik Sehat Hewan')
                ->assertSee('Salon Bulu Cantik')
                ->assertDontSee('Klinik Belum Diverifikasi')
                ->assertSee('Jl. Ijen, Malang');
        });
    }

    public function test_client_can_search_for_a_clinic(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic'))
                ->waitForText('Klinik yang Tersedia')
                ->type(self::SEARCH_INPUT, 'Salon')
                ->waitUntilMissingText('Klinik Sehat Hewan')
                ->assertSee('Salon Bulu Cantik')
                ->assertSee('untuk "Salon"');
        });
    }

    public function test_client_sees_an_empty_state_when_nothing_matches(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic'))
                ->waitForText('Klinik yang Tersedia')
                ->type(self::SEARCH_INPUT, 'tidak ada klinik ini')
                ->waitForText('Mitra tidak ditemukan')
                ->assertSee('Coba ubah filter pencarian Anda')
                ->assertDontSee('Klinik Sehat Hewan');
        });
    }

    public function test_client_can_filter_by_service_category_and_clear_the_filter(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic'))
                ->waitForText('Klinik yang Tersedia')
                ->press('Grooming')
                ->waitUntilMissingText('Klinik Sehat Hewan')
                ->assertSee('Salon Bulu Cantik')
                ->press('Grooming')
                ->waitForText('Klinik Sehat Hewan')
                ->assertSee('Salon Bulu Cantik')
                ->press('Clinic')
                ->waitUntilMissingText('Salon Bulu Cantik')
                ->assertSee('Klinik Sehat Hewan')
                ->press('Semua')
                ->waitForText('Salon Bulu Cantik')
                ->assertSee('Klinik Sehat Hewan');
        });
    }

    public function test_search_and_category_are_kept_in_the_url(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic'))
                ->waitForText('Klinik yang Tersedia')
                ->type(self::SEARCH_INPUT, 'Salon')
                ->waitUntilMissingText('Klinik Sehat Hewan');

            $this->assertStringContainsString('q=Salon', $browser->driver->getCurrentURL());
        });
    }

    public function test_client_can_open_the_detail_page_from_a_card(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->client, 'client')
                ->visit(route('client.clinic'))
                ->waitForText('Klinik Sehat Hewan')
                ->clickLink('Klinik Sehat Hewan')
                ->waitForText('Reservasi Sekarang')
                ->assertPathIs('/clinic/klinik-sehat-hewan');
        });
    }
}
