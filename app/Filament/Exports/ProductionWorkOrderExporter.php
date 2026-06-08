<?php

namespace App\Filament\Exports;

use App\Models\ProductionWorkOrder;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ProductionWorkOrderExporter extends Exporter
{
    protected static ?string $model = ProductionWorkOrder::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('order.id'),
            ExportColumn::make('queue.name'),
            ExportColumn::make('currentStage.name'),
            ExportColumn::make('assignedPersonnel.id'),
            ExportColumn::make('status'),
            ExportColumn::make('deadline_band'),
            ExportColumn::make('priority_tier'),
            ExportColumn::make('dynamic_score'),
            ExportColumn::make('estimated_minutes'),
            ExportColumn::make('remaining_steps'),
            ExportColumn::make('remaining_minutes'),
            ExportColumn::make('blocked_reason'),
            ExportColumn::make('blocked_severity'),
            ExportColumn::make('dependencies_met'),
            ExportColumn::make('is_pinned'),
            ExportColumn::make('pinned_expires_at'),
            ExportColumn::make('is_held'),
            ExportColumn::make('hold_reason'),
            ExportColumn::make('manual_sort_index'),
            ExportColumn::make('override_assigned_to'),
            ExportColumn::make('meaningful_progress_at'),
            ExportColumn::make('latest_progress_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your production work order export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
