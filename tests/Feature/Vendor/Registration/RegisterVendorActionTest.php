<?php

namespace Tests\Feature\Vendor\Registration;

use App\Actions\Vendor\RegisterVendorAction;
use App\DTOs\RegisterVendorData;
use App\Enums\DayOfWeek;
use App\Models\Role;
use App\Models\VendorOperatingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterVendorActionTest extends TestCase
{
    use RefreshDatabase;

    private function registrationData(): RegisterVendorData
    {
        return new RegisterVendorData(
            vendorName: 'Klinik Hewan Sehat',
            address: 'Jl. Soekarno Hatta No. 1, Malang',
            contact: '0341123456',
            emailVendor: 'klinik@sehat.test',
            email: 'owner@sehat.test',
            description: 'Klinik hewan terpercaya',
            ownerName: 'Drh. Rani',
            phoneNumber: '081234567890',
            password: 'password-owner-123',
        );
    }

    public function test_registering_a_vendor_creates_default_operating_hours_for_every_day(): void
    {
        Role::firstOrCreate(['name' => 'owner']);

        $vendor = app(RegisterVendorAction::class)->execute($this->registrationData());

        $hours = VendorOperatingHour::withoutGlobalScopes()
            ->where('vendor_id', $vendor->id)
            ->orderBy('day_of_week')
            ->get();

        $this->assertCount(7, $hours);
        $this->assertSame(
            array_map(fn (DayOfWeek $day): int => $day->value, DayOfWeek::cases()),
            $hours->map(fn (VendorOperatingHour $hour): int => $hour->day_of_week->value)->all(),
        );

        foreach ($hours as $hour) {
            $this->assertFalse($hour->is_closed);
            $this->assertSame('09:00', substr($hour->open_time, 0, 5));
            $this->assertSame('17:00', substr($hour->close_time, 0, 5));
        }
    }

    public function test_each_vendor_gets_its_own_operating_hours(): void
    {
        Role::firstOrCreate(['name' => 'owner']);

        $first = app(RegisterVendorAction::class)->execute($this->registrationData());

        $second = app(RegisterVendorAction::class)->execute(new RegisterVendorData(
            vendorName: 'Pet Care Lain',
            address: 'Jl. Veteran No. 2, Malang',
            contact: '0341765432',
            emailVendor: 'lain@petcare.test',
            email: 'owner2@petcare.test',
            description: 'Pet care lain',
            ownerName: 'Budi',
            phoneNumber: '081298765432',
            password: 'password-owner-123',
        ));

        foreach ([$first, $second] as $vendor) {
            $this->assertSame(
                7,
                VendorOperatingHour::withoutGlobalScopes()->where('vendor_id', $vendor->id)->count(),
            );
        }
    }
}
