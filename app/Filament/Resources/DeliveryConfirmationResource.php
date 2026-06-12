<?php

namespace App\Filament\Resources;

use App\Models\Order;
use App\Enums\OrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\DeliveryConfirmationResource\Pages;

class DeliveryConfirmationResource extends Resource
{
    protected static ?string $model = Order::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-check-badge';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Layanan Pelanggan';
    }

    public static function getNavigationLabel(): string
    {
        return 'Konfirmasi Pengiriman';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Konfirmasi Pengiriman';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('status', OrderStatus::SHIPPED))
            ->columns([
                Tables\Columns\TextColumn::make('order_code')
                    ->label('Kode Pesanan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('deadline_at')
                    ->label('Tenggat')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\Action::make('diterima')
                    ->label('Pesanan Diterima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Pesanan Diterima')
                    ->modalDescription('Apakah Anda yakin pesanan ini telah diterima dengan baik tanpa komplain?')
                    ->action(function (Order $record) {
                        $record->update(['status' => OrderStatus::COMPLETED]);
                        \Filament\Notifications\Notification::make()
                            ->title('Pesanan diselesaikan')
                            ->success()
                            ->send();
                    }),

                \Filament\Actions\Action::make('retur')
                    ->label('Retur')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Retur / Komplain')
                            ->required(),
                        Forms\Components\Select::make('resolution')
                            ->label('Solusi yang Diharapkan')
                            ->options(\App\Enums\ReturnResolution::class)
                            ->required(),
                        Forms\Components\FileUpload::make('photo_proof_path')
                            ->label('Foto Bukti (Opsional)')
                            ->image()
                            ->directory('return_proofs'),
                    ])
                    ->modalHeading('Retur Pesanan')
                    ->action(function (array $data, Order $record) {
                        // Create Return record
                        \App\Models\OrderReturn::create([
                            'order_id' => $record->id,
                            'reported_by_id' => auth()->id(),
                            'reason' => $data['reason'],
                            'resolution' => $data['resolution'],
                            'photo_proof_path' => $data['photo_proof_path'] ?? null,
                            'status' => \App\Enums\ReturnStatus::PENDING,
                            'priority' => 'NORMAL', // Default, can be adjusted
                        ]);

                        // Update order status
                        $record->update(['status' => OrderStatus::RETURNED]);

                        \Filament\Notifications\Notification::make()
                            ->title('Laporan Retur berhasil dibuat')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryConfirmations::route('/'),
        ];
    }
}
