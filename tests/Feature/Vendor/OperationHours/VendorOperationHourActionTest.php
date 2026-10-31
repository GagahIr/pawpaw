<?php

namespace Tests\Feature\Vendor\OperationHours;

use App\Actions\Admin\VendorOperationHour\VendorOperationHourAction;
use App\Models\Vendor;
use App\Models\VendorOperatingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorOperationHourActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_day_is_saved_without_open_and_close_time(): void
    {
        $vendor = Vendor::factory()->create();

        app(VendorOperationHourAction::class)->execute($vendor->id, [
            ['day_of_week' => 0, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => true],
            ['day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
        ]);

        $closed = VendorOperatingHour::where('vendor_id', $vendor->id)->where('day_of_week', 0)->first();
        $open = VendorOperatingHour::where('vendor_id', $vendor->id)->where('day_of_week', 1)->first();

        $this->assertTrue($closed->is_closed);
        $this->assertNull($closed->open_time);
        $this->assertNull($closed->close_time);
        $this->assertFalse($open->is_closed);
        $this->assertSame('09:00', substr($open->open_time, 0, 5));
    }
}
