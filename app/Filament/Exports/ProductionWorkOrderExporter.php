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

    public static function modifyQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->with([
            'order.account',
            'order.designTasks.assignedDesigner',
            'progressLogs.stage',
            'progressLogs.personnel',
        ]);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('order.order_code')
                ->label('NO'),
            ExportColumn::make('rincian_pekerjaan')
                ->label('RINCIAN PEKERJAAN')
                ->state(fn ($record) => ($record->order?->account?->name ?? '-') . " - " . ($record->order?->product_sentence ?? '-')),
            ExportColumn::make('desain')
                ->label('DESAIN')
                ->state(fn ($record) => $record->order?->designTasks->first()?->assignedDesigner?->full_name ?? '-'),
            ExportColumn::make('durasi')
                ->label('DURASI')
                ->state(fn ($record) => max(0, \Carbon\Carbon::now()->diffInDays($record->order?->deadline_at)) . ' HARI'),
            ExportColumn::make('status')
                ->label('STATUS')
                ->state(fn ($record) => str_replace(['🔴 ', '🟠 ', '🟡 ', '🟢 ', '✅ '], '', $record->deadlineBandLabel())),
            ExportColumn::make('las')
                ->label('LAS')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'LAS')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('laser')
                ->label('LASER')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'LASER')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('rangkai')
                ->label('RANGKAI')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'RANGKAI')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('stcr_uv')
                ->label('STRC UV')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'STCR_UV')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('cd')
                ->label('CD')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'CD')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('finishing')
                ->label('FINISHING')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'FINISHING')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('bubble')
                ->label('BUBBLE')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'BUBBLE')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('kirim')
                ->label('KIRIM')
                ->state(fn ($record) => $record->progressLogs->where('stage.code', 'DATE')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? ''),
            ExportColumn::make('catatan')
                ->label('CATATAN')
                ->state(fn ($record) => $record->order?->admin_notes ?? ''),
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
