<?php

namespace App\Filament\Owner\Resources\VendorServices\Pages;

use App\Filament\Owner\Resources\VendorServices\VendorServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorService extends EditRecord
{
    protected static string $resource = VendorServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
