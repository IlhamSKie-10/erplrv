<?php

namespace App\Filament\Resources;

use App\Enums\ReminderStatus;
use App\Filament\Resources\ReminderResource\Pages;
use App\Models\Reminder;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReminderResource extends Resource
{
    protected static ?string $model = Reminder::class;

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'Reminder';

    protected static ?string $pluralModelLabel = 'Reminders';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public static function getNavigationLabel(): string
    {
        return 'Reminders';
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

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('assignee_id')
                ->label('Ditugaskan Ke')
                ->relationship('assignee', 'full_name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('title')
                ->label('Judul')
                ->required(),

            Forms\Components\Textarea::make('message')
                ->label('Pesan')
                ->required()
                ->rows(3),

            Forms\Components\DateTimePicker::make('due_at')
                ->label('Jatuh Tempo')
                ->required(),

            Forms\Components\DateTimePicker::make('remind_at')
                ->label('Ingatkan Pada')
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(ReminderStatus::class)
                ->default(ReminderStatus::PENDING->value)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('due_at')
                    ->label('Jatuh Tempo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable(),

                Tables\Columns\TextColumn::make('assignee.full_name')
                    ->label('Ditugaskan Ke')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING'   => 'warning',
                        'COMPLETED' => 'success',
                        'SNOOZED'   => 'info',
                        'CANCELLED' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('due_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ReminderStatus::class)
                    ->default(ReminderStatus::PENDING->value),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReminders::route('/'),
        ];
    }
}
