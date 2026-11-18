<?php

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\CategoryService;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesClientBookingData;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, CreatesClientBookingData::class);

beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

    $this->client = $this->createClientUser();
});

function listedVendor(object $test, string $name, string $category = 'Clinic', array $attributes = []): Vendor
{
    $vendor = $test->createVendor(['name' => $name] + $attributes);
    $test->addService($vendor, $category);

    return $vendor;
}

function clinicList(object $test)
{
    return Livewire::actingAs($test->client, 'client')->test('client.list_clinic.list-clinic2');
}

/*
|--------------------------------------------------------------------------
| HTTP: akses halaman dan isi daftar klinik
|--------------------------------------------------------------------------
*/

it('redirects guests to the client login page', function () {
    $this->get(route('client.clinic'))->assertRedirect(route('client.login'));
});

it('forbids authenticated users who are not clients', function () {
    $owner = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'owner'])->id]);

    $this->actingAs($owner, 'client')->withoutVite()->get(route('client.clinic'))->assertForbidden();
});

it('shows the heading, search box and every service category as a filter', function () {
    CategoryService::firstOrCreate(['name' => 'Clinic']);
    CategoryService::firstOrCreate(['name' => 'Grooming']);
    CategoryService::firstOrCreate(['name' => 'Pet Hotel']);

    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.clinic'))
        ->assertOk()
        ->assertSee('Klinik yang Tersedia')
        ->assertSee('Cari klinik atau grooming...', false)
        ->assertSeeInOrder(['Semua', 'Clinic', 'Grooming', 'Pet Hotel']);
});

it('renders a clinic card with name, address, contact, service badge and a link to the detail page', function () {
    $vendor = listedVendor($this, 'Klinik Sehat Hewan', attributes: [
        'address' => 'Jl. Veteran No. 10, Malang',
        'contact' => '081355556666',
    ]);

    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.clinic'))
        ->assertOk()
        ->assertSee('Klinik Sehat Hewan')
        ->assertSee('Jl. Veteran No. 10, Malang')
        ->assertSee('081355556666')
        ->assertSee('Clinic')
        ->assertSee('href="'.route('client.vendor.detail', $vendor->slug).'"', false);
});

it('shows the total of listed clinics in the result summary', function () {
    listedVendor($this, 'Klinik Satu');
    listedVendor($this, 'Klinik Dua');

    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.clinic'))
        ->assertOk()
        ->assertSeeInOrder(['Menampilkan', '2', 'dari', '2', 'mitra']);
});

it('escapes clinic names so markup is never rendered', function () {
    $name = '<script>alert("klinik")</script>';
    listedVendor($this, $name);

    $this->actingAs($this->client, 'client')->withoutVite()->get(route('client.clinic'))
        ->assertOk()
        ->assertDontSee($name, false);
});

/*
|--------------------------------------------------------------------------
| Aturan klinik mana yang boleh tampil
|--------------------------------------------------------------------------
*/

it('lists only verified clinics with an active subscription and an active service', function () {
    listedVendor($this, 'Klinik Layak Tampil');
    listedVendor($this, 'Klinik Belum Diverifikasi', attributes: ['status_verification' => VendorVerificationStatus::Pending]);
    listedVendor($this, 'Klinik Ditolak', attributes: ['status_verification' => VendorVerificationStatus::Rejected]);
    listedVendor($this, 'Klinik Langganan Nonaktif', attributes: ['status_subscription' => VendorSubscriptionStatus::Inactive]);
    listedVendor($this, 'Klinik Langganan Kedaluwarsa', attributes: ['subscription_expired_at' => now()->subDay()]);

    $noService = $this->createVendor(['name' => 'Klinik Tanpa Layanan']);
    $inactiveService = $this->createVendor(['name' => 'Klinik Layanan Mati']);
    $this->addService($inactiveService, active: false);

    clinicList($this)
        ->assertSee('Klinik Layak Tampil')
        ->assertDontSee('Klinik Belum Diverifikasi')
        ->assertDontSee('Klinik Ditolak')
        ->assertDontSee('Klinik Langganan Nonaktif')
        ->assertDontSee('Klinik Langganan Kedaluwarsa')
        ->assertDontSee($noService->name)
        ->assertDontSee('Klinik Layanan Mati');
});

it('sorts clinics alphabetically by name', function () {
    listedVendor($this, 'Zebra Pet Care');
    listedVendor($this, 'Alfa Vet');
    listedVendor($this, 'Mitra Satwa');

    clinicList($this)->assertSeeInOrder(['Alfa Vet', 'Mitra Satwa', 'Zebra Pet Care']);
});

/*
|--------------------------------------------------------------------------
| Pencarian
|--------------------------------------------------------------------------
*/

it('searches clinics by name regardless of letter case', function () {
    listedVendor($this, 'Klinik Sehat Hewan');
    listedVendor($this, 'Grooming Bulu Cantik');

    clinicList($this)
        ->set('search', 'sehat')
        ->assertSee('Klinik Sehat Hewan')
        ->assertDontSee('Grooming Bulu Cantik');
});

it('searches clinics by address', function () {
    listedVendor($this, 'Klinik Utara', attributes: ['address' => 'Jl. Merdeka Raya, Surabaya']);
    listedVendor($this, 'Klinik Selatan', attributes: ['address' => 'Jl. Ijen, Malang']);

    clinicList($this)
        ->set('search', 'Surabaya')
        ->assertSee('Klinik Utara')
        ->assertDontSee('Klinik Selatan');
});

it('requires every word of the search to match the name or the address', function () {
    listedVendor($this, 'Klinik Sehat Hewan', attributes: ['address' => 'Jl. Ijen, Malang']);
    listedVendor($this, 'Klinik Sehat Satwa', attributes: ['address' => 'Jl. Merdeka, Surabaya']);

    clinicList($this)
        ->set('search', 'sehat malang')
        ->assertSee('Klinik Sehat Hewan')
        ->assertDontSee('Klinik Sehat Satwa');
});

it('ignores extra whitespace around the search words', function () {
    listedVendor($this, 'Klinik Sehat Hewan');

    clinicList($this)
        ->set('search', '   klinik    sehat  ')
        ->assertSee('Klinik Sehat Hewan');
});

it('echoes the search keyword in the result summary', function () {
    listedVendor($this, 'Klinik Sehat Hewan');

    clinicList($this)
        ->set('search', 'Sehat')
        ->assertSee('untuk "Sehat"', false);
});

it('shows an empty state when nothing matches', function () {
    listedVendor($this, 'Klinik Sehat Hewan');

    clinicList($this)
        ->set('search', 'tidak-ada-klinik-ini')
        ->assertSee('Mitra tidak ditemukan')
        ->assertSee('Coba ubah filter pencarian Anda')
        ->assertDontSee('Klinik Sehat Hewan');
});

/*
|--------------------------------------------------------------------------
| Filter kategori layanan
|--------------------------------------------------------------------------
*/

it('filters clinics by service category', function () {
    listedVendor($this, 'Klinik Sehat Hewan', 'Clinic');
    listedVendor($this, 'Salon Bulu Cantik', 'Grooming');

    $grooming = CategoryService::where('name', 'Grooming')->first();

    clinicList($this)
        ->call('selectCategory', $grooming->id)
        ->assertSet('categoryId', $grooming->id)
        ->assertSee('Salon Bulu Cantik')
        ->assertDontSee('Klinik Sehat Hewan');
});

it('clears the category filter when the same category is selected again', function () {
    listedVendor($this, 'Klinik Sehat Hewan', 'Clinic');
    listedVendor($this, 'Salon Bulu Cantik', 'Grooming');

    $grooming = CategoryService::where('name', 'Grooming')->first();

    clinicList($this)
        ->call('selectCategory', $grooming->id)
        ->call('selectCategory', $grooming->id)
        ->assertSet('categoryId', null)
        ->assertSee('Klinik Sehat Hewan')
        ->assertSee('Salon Bulu Cantik');
});

it('shows all clinics again with the Semua filter', function () {
    listedVendor($this, 'Klinik Sehat Hewan', 'Clinic');
    listedVendor($this, 'Salon Bulu Cantik', 'Grooming');

    $clinic = CategoryService::where('name', 'Clinic')->first();

    clinicList($this)
        ->call('selectCategory', $clinic->id)
        ->assertDontSee('Salon Bulu Cantik')
        ->call('selectCategory', null)
        ->assertSet('categoryId', null)
        ->assertSee('Klinik Sehat Hewan')
        ->assertSee('Salon Bulu Cantik');
});

it('combines the search keyword with the category filter', function () {
    $sehat = listedVendor($this, 'Klinik Sehat Hewan', 'Clinic');
    $this->addService($sehat, 'Grooming');
    listedVendor($this, 'Sehat Grooming Studio', 'Grooming');
    listedVendor($this, 'Klinik Prima', 'Clinic');

    $clinic = CategoryService::where('name', 'Clinic')->first();

    clinicList($this)
        ->set('search', 'sehat')
        ->call('selectCategory', $clinic->id)
        ->assertSee('Klinik Sehat Hewan')
        ->assertDontSee('Sehat Grooming Studio')
        ->assertDontSee('Klinik Prima');
});

it('ignores services that are switched off when filtering by category', function () {
    $vendor = $this->createVendor(['name' => 'Klinik Sehat Hewan']);
    $this->addService($vendor, 'Clinic');
    $this->addService($vendor, 'Grooming', active: false);

    $grooming = CategoryService::where('name', 'Grooming')->first();

    clinicList($this)
        ->call('selectCategory', $grooming->id)
        ->assertDontSee('Klinik Sehat Hewan')
        ->assertSee('Mitra tidak ditemukan');
});

/*
|--------------------------------------------------------------------------
| Pagination dan URL
|--------------------------------------------------------------------------
*/

it('paginates the clinic list by 12 per page', function () {
    foreach (range(1, 13) as $number) {
        listedVendor($this, sprintf('Klinik Nomor %02d', $number));
    }

    clinicList($this)
        ->assertSee('Klinik Nomor 12')
        ->assertDontSee('Klinik Nomor 13')
        ->call('gotoPage', 2)
        ->assertSee('Klinik Nomor 13')
        ->assertDontSee('Klinik Nomor 01');
});

it('returns to the first page when the search changes', function () {
    foreach (range(1, 13) as $number) {
        listedVendor($this, sprintf('Klinik Nomor %02d', $number));
    }

    clinicList($this)
        ->call('gotoPage', 2)
        ->set('search', 'Klinik Nomor 0')
        ->assertSee('Klinik Nomor 01')
        ->assertDontSee('Klinik Nomor 13');
});

it('restores the search and category filter from the query string', function () {
    listedVendor($this, 'Klinik Sehat Hewan', 'Clinic');
    listedVendor($this, 'Salon Sehat', 'Grooming');

    $grooming = CategoryService::where('name', 'Grooming')->first();

    Livewire::actingAs($this->client, 'client')
        ->withQueryParams(['q' => 'Sehat', 'layanan' => $grooming->id])
        ->test('client.list_clinic.list-clinic2')
        ->assertSet('search', 'Sehat')
        ->assertSet('categoryId', $grooming->id)
        ->assertSee('Salon Sehat')
        ->assertDontSee('Klinik Sehat Hewan');
});
