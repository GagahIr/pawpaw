<?php

namespace App\Filament\Owner\Resources\VendorServices\Schemas;

use App\Models\VendorStaff;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('vendor_id')
                    ->default(fn() => VendorStaff::where('user_id', auth()->id())->value('vendor_id'))
                    ->required(),


            ]);
    }
}
