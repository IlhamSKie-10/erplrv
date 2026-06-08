<?php

namespace App\Filament\Resources\ProductionWorkOrderResource\Pages;

use App\Filament\Resources\ProductionWorkOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListProductionWorkOrders extends ListRecords
{
    protected static string $resource = ProductionWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ExportAction::make()
                ->exporter(\App\Filament\Exports\ProductionWorkOrderExporter::class),
        ];
    }
}
