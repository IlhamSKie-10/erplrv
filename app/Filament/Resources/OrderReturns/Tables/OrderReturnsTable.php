<?php

namespace App\Filament\Resources\OrderReturns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_code')
                    ->label('Kode Pesanan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reportedBy.full_name')
                    ->label('Dilaporkan Oleh')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->searchable(),
                \Filament\Tables\Columns\ImageColumn::make('photo_proof_path')
                    ->label('Bukti'),
                TextColumn::make('resolution')
                    ->label('Penyelesaian')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state->value) {
                        'PENDING' => 'warning',
                        'IN_PROGRESS' => 'info',
                        'RESOLVED' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'HIGH' => 'danger',
                        'NORMAL' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('teruskan_produksi')
                    ->label('Teruskan ke Produksi')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim ke Produksi?')
                    ->modalDescription('Ini akan membuat Work Order baru dengan status REWORK untuk tim produksi.')
                    ->visible(fn (\App\Models\OrderReturn $record) => $record->resolution->value === 'REWORK' && $record->status->value !== 'RESOLVED')
                    ->action(function (\App\Models\OrderReturn $record) {
                        $record->update(['status' => \App\Enums\ReturnStatus::IN_PROGRESS]);
                        
                        // Buat ProductionWorkOrder REWORK
                        $order = $record->order;
                        $firstStage = \App\Models\ProductionStage::orderBy('sort_order')->first();
                        $queue = \App\Models\ProductionQueue::first();
                        if ($order && $firstStage && $queue) {
                            \App\Models\ProductionWorkOrder::create([
                                'order_id' => $order->id,
                                'queue_id' => $queue->id,
                                'current_stage_id' => $firstStage->id,
                                'status' => \App\Enums\ProgressStatus::REWORK,
                                'remaining_minutes' => 60,
                                'priority_tier' => \App\Enums\PriorityTier::URGENT,
                                'dynamic_score' => 99,
                                'estimated_minutes' => 60,
                                'remaining_steps' => 5,
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Work Order REWORK berhasil dibuat')
                                ->success()
                                ->send();
                        }
                    }),
                \Filament\Actions\Action::make('resolve')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\OrderReturn $record) => $record->status->value !== 'RESOLVED')
                    ->action(function (\App\Models\OrderReturn $record) {
                        $record->update(['status' => \App\Enums\ReturnStatus::RESOLVED]);
                        \Filament\Notifications\Notification::make()
                            ->title('Retur ditandai selesai')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
