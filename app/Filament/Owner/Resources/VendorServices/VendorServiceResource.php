<?php

namespace App\Filament\Owner\Resources\VendorServices;

use App\Filament\Owner\Resources\VendorServices\Pages\CreateVendorService;
use App\Filament\Owner\Resources\VendorServices\Pages\EditVendorService;
use App\Filament\Owner\Resources\VendorServices\Pages\ListVendorServices;
use App\Filament\Owner\Resources\VendorServices\Schemas\VendorServiceForm;
use App\Filament\Owner\Resources\VendorServices\Tables\VendorServicesTable;
use App\Models\VendorService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorServiceResource extends Resource
{
    protected static ?string $model = VendorService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return VendorServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorServicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VendorServiceItemsRelationManager::class,
        ];
    }

    // public function canCreate(): bool
    // {
    //     return false;
    // }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorServices::route('/'),
            // 'create' => CreateVendorService::route('/create'),
            'edit' => EditVendorService::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
