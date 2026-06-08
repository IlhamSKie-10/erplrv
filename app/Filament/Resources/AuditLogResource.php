<?php

namespace App\Filament\Resources;

use App\Enums\AuditAction;
use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationLabel(): string
    {
        return 'Log Aktivitas';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan & Admin';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['MANAGER', 'SUPER_ADMIN', 'DEVELOPER']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('entity_type')->label('Entitas')->disabled(),
            Forms\Components\TextInput::make('entity_id')->label('ID Entitas')->disabled(),
            Forms\Components\TextInput::make('action')->label('Aksi')->disabled(),
            Forms\Components\Textarea::make('summary')->label('Ringkasan')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actorUser.full_name')
                    ->label('Pelaku')
                    ->searchable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CREATE'        => 'info',
                        'UPDATE', 'STATUS_CHANGE' => 'warning',
                        'SOFT_DELETE'   => 'danger',
                        'SUBMIT', 'FORWARD', 'APPROVE' => 'success',
                        default         => 'gray',
                    }),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Entitas')
                    ->badge(),

                Tables\Columns\TextColumn::make('summary')
                    ->label('Ringkasan')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->summary),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Aksi')
                    ->options(AuditAction::class),

                Tables\Filters\SelectFilter::make('entity_type')
                    ->label('Entitas')
                    ->options([
                        'order'      => 'Pesanan',
                        'design'     => 'Desain',
                        'production' => 'Produksi',
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view'  => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
