<?php

namespace App\Exports;

use App\Models\ProductionWorkOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Database\Eloquent\Builder;

class ProductionSpreadsheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->with([
            'order.account',
            'order.designTasks.assignedDesigner',
            'progressLogs.stage',
            'progressLogs.personnel',
        ])->get();
    }

    public function headings(): array
    {
        return [
            'NO',
            'RINCIAN PEKERJAAN',
            'DESAIN',
            'DURASI',
            'STATUS',
            'LAS',
            'LASER',
            'RANGKAI',
            'STRC UV',
            'CD',
            'FINISHING',
            'BUBBLE',
            'KIRIM',
            'CATATAN',
        ];
    }

    public function map($record): array
    {
        return [
            $record->order?->order_code ?? '-',
            ($record->order?->account?->name ?? '-') . " - " . ($record->order?->product_sentence ?? '-'),
            $record->order?->designTasks->first()?->assignedDesigner?->full_name ?? '-',
            max(0, \Carbon\Carbon::now()->diffInDays($record->order?->deadline_at)) . ' HARI',
            str_replace(['🔴 ', '🟠 ', '🟡 ', '🟢 ', '✅ '], '', $record->deadlineBandLabel()),
            $record->progressLogs->where('stage.code', 'LAS')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->progressLogs->where('stage.code', 'LASER')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->progressLogs->where('stage.code', 'RANGKAI')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->progressLogs->where('stage.code', 'STCR_UV')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->progressLogs->where('stage.code', 'CD')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->progressLogs->where('stage.code', 'FINISHING')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->progressLogs->where('stage.code', 'BUBBLE')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->progressLogs->where('stage.code', 'DATE')->where('status.value', 'COMPLETED')->first()?->personnel?->full_name ?? '',
            $record->order?->admin_notes ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // NO
            'B' => 45, // RINCIAN PEKERJAAN
            'C' => 20, // DESAIN
            'D' => 15, // DURASI
            'E' => 20, // STATUS
            'F' => 15, // LAS
            'G' => 15, // LASER
            'H' => 15, // RANGKAI
            'I' => 15, // STRC UV
            'J' => 15, // CD
            'K' => 15, // FINISHING
            'L' => 15, // BUBBLE
            'M' => 15, // KIRIM
            'N' => 35, // CATATAN
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling header
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F46E5'], // Indigo/Filament primary
            ],
        ]);

        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            // Kolom DESAIN diberi background warna Hijau (Kolom C)
            $sheet->getStyle('C' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFdcfce7'], // Light green
                ],
            ]);

            // Jika Status "TERLAMBAT", sel terkait akan diblok warna Merah bata (Kolom E)
            $statusValue = $sheet->getCell('E' . $row)->getValue();
            if (str_contains(strtoupper($statusValue), 'TERLAMBAT')) {
                $sheet->getStyle('E' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFB22222'], // FireBrick / Merah Bata
                    ],
                    'font' => [
                        'color' => ['argb' => Color::COLOR_WHITE],
                        'bold' => true,
                    ],
                ]);
            }
        }

        return [];
    }
}
