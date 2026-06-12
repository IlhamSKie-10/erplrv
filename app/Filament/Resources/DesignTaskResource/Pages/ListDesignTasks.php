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
            'Semua' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereNull('forwarded_at')),
            'Homedecor' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereNull('forwarded_at')->whereHas('order.productType', fn ($q) => $q->where('name', 'Home Decor'))),
            'Logo Tukir' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereNull('forwarded_at')->whereHas('order.productType', fn ($q) => $q->where('name', 'Logo & Ukir'))),
            'Advertising 1' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereNull('forwarded_at')->whereHas('order.productType', fn ($q) => $q->where('name', 'Advertising 1'))),
            'Advertising 2' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereNull('forwarded_at')->whereHas('order.productType', fn ($q) => $q->where('name', 'Advertising 2'))),
            'Arsip' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereNotNull('forwarded_at')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
