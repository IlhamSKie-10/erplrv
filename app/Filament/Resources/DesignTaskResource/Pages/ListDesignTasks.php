<?php

namespace App\Filament\Resources\DesignTaskResource\Pages;

use App\Filament\Resources\DesignTaskResource;
use Filament\Resources\Pages\ListRecords;

class ListDesignTasks extends ListRecords
{
    protected static string $resource = DesignTaskResource::class;

    public function getTitle(): string
    {
        return 'Papan Antrean Desain';
    }

    public function getTabs(): array
    {
        return [
            'Semua' => \Filament\Schemas\Components\Tabs\Tab::make(),
            'Home Decor' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('order.productType', fn ($q) => $q->where('name', 'Home Decor'))),
            'Advertising 1' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('order.productType', fn ($q) => $q->where('name', 'Advertising 1'))),
            'Advertising 2' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('order.productType', fn ($q) => $q->where('name', 'Advertising 2'))),
            'Logo & Ukir' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('order.productType', fn ($q) => $q->where('name', 'Logo & Ukir'))),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
