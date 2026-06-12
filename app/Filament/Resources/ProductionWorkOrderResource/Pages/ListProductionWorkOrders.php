<?php

namespace App\Filament\Resources\ProductionWorkOrderResource\Pages;

use App\Filament\Resources\ProductionWorkOrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Exports\ProductionSpreadsheetExport;
use Maatwebsite\Excel\Facades\Excel;

class ListProductionWorkOrders extends ListRecords
{
    protected static string $resource = ProductionWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery()
                        ->whereNotIn('status', [
                            \App\Enums\ProgressStatus::NOT_STARTED,
                            \App\Enums\ProgressStatus::COMPLETED,
                            \App\Enums\ProgressStatus::DONE,
                        ]);
                    return Excel::download(new ProductionSpreadsheetExport($query), 'produksi_' . date('YmdHis') . '.xlsx');
                }),
        ];
    }
}
