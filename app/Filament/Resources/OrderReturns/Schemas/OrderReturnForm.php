<?php

namespace App\Filament\Resources\OrderReturns\Schemas;

use App\Enums\ReturnResolution;
use App\Enums\ReturnStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Retur')
                    ->schema([
                        Select::make('order_id')
                            ->label('Pesanan (Order)')
                            ->relationship('order', 'order_code')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('reported_by_id')
                            ->label('Dilaporkan Oleh')
                            ->relationship('reportedBy', 'full_name')
                            ->default(fn () => auth()->id())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Select::make('priority')
                            ->label('Prioritas')
                            ->options([
                                'NORMAL' => 'Normal',
                                'HIGH' => 'Tinggi (HIGH)',
                            ])
                            ->required()
                            ->default('NORMAL'),
                        Select::make('status')
                            ->label('Status Retur')
                            ->options(ReturnStatus::class)
                            ->default(ReturnStatus::PENDING)
                            ->required(),
                        Select::make('resolution')
                            ->label('Jenis Penyelesaian (Resolution)')
                            ->options(ReturnResolution::class)
                            ->required(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Detail Komplain')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Alasan Retur / Komplain')
                            ->required()
                            ->columnSpanFull(),
                        \Filament\Forms\Components\FileUpload::make('photo_proof_path')
                            ->label('Foto Bukti')
                            ->image()
                            ->directory('return-proofs')
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Catatan Penyelesaian')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
