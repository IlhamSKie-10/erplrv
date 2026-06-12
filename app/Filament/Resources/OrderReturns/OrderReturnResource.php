<?php

namespace App\Filament\Resources\OrderReturns;

use App\Filament\Resources\OrderReturns\Pages\CreateOrderReturn;
use App\Filament\Resources\OrderReturns\Pages\EditOrderReturn;
use App\Filament\Resources\OrderReturns\Pages\ListOrderReturns;
use App\Filament\Resources\OrderReturns\Schemas\OrderReturnForm;
use App\Filament\Resources\OrderReturns\Tables\OrderReturnsTable;
use App\Models\OrderReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderReturnResource extends Resource
{
    protected static ?string $model = OrderReturn::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-uturn-left';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Layanan Pelanggan';
    }

    public static function getNavigationLabel(): string
    {
        return 'Daftar Pengerjaan Retur';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Retur Pesanan';
    }

    public static function form(Schema $schema): Schema
    {
        return OrderReturnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderReturnsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderReturns::route('/'),
            'create' => CreateOrderReturn::route('/create'),
            'edit' => EditOrderReturn::route('/{record}/edit'),
        ];
    }
}
