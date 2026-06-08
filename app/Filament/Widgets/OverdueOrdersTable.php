<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Order;

class OverdueOrdersTable extends TableWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Peringatan: 5 Pesanan Mendekati/Lewat Deadline';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->whereNotIn('status', [\App\Enums\OrderStatus::COMPLETED, \App\Enums\OrderStatus::SHIPPED])
                    ->whereNotNull('deadline_at')
                    ->orderBy('deadline_at', 'asc')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('order_code')
                    ->label('Kode Order')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                \Filament\Tables\Columns\TextColumn::make('account.name')
                    ->label('Customer')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Status Terakhir')
                    ->badge(),

                \Filament\Tables\Columns\TextColumn::make('deadline_at')
                    ->label('Deadline')
                    ->dateTime('d M Y, H:i')
                    ->color(fn (Order $record) => $record->deadline_at < now() ? 'danger' : 'warning')
                    ->weight(fn (Order $record) => $record->deadline_at < now() ? 'bold' : 'normal')
                    ->sortable(),
            ]);
    }
}
