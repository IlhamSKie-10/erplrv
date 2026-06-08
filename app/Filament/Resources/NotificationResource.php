<?php

namespace App\Filament\Resources;

use App\Enums\NotificationType;
use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Notifikasi Sistem';

    protected static ?string $pluralModelLabel = 'Notifikasi Sistem';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bell';
    }

    public static function getNavigationLabel(): string
    {
        return 'Notifikasi';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan & Admin';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['SUPER_ADMIN', 'DEVELOPER']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Tipe')
                ->options(NotificationType::class)
                ->disabled(),
            Forms\Components\TextInput::make('title')->label('Judul')->disabled(),
            Forms\Components\Textarea::make('message')->label('Pesan')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(50),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(NotificationType::class),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
