<?php

namespace App\Filament\Resources;

use App\Enums\DeadlineBand;
use App\Enums\ProgressStatus;
use App\Filament\Resources\ProductionWorkOrderResource\Pages;
use App\Models\ProductionWorkOrder;
use App\Services\ProductionService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionWorkOrderResource extends Resource
{
    protected static ?string $model = ProductionWorkOrder::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Work Order';

    protected static ?string $pluralModelLabel = 'Work Orders Produksi';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationLabel(): string
    {
        return 'Work Orders';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Produksi';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['PRODUCTION', 'MANAGER', 'SUPER_ADMIN', 'DEVELOPER']);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Informasi Pesanan')
                ->schema([
                    Forms\Components\Placeholder::make('order_code')
                        ->label('Kode Pesanan')
                        ->content(fn (ProductionWorkOrder $record) => $record->order?->order_code ?? '-'),

                    Forms\Components\Placeholder::make('product_sentence')
                        ->label('Deskripsi Produk')
                        ->content(fn (ProductionWorkOrder $record) => $record->order?->product_sentence ?? '-'),

                    Forms\Components\Placeholder::make('deadline_at')
                        ->label('Deadline')
                        ->content(fn (ProductionWorkOrder $record) => $record->order?->deadline_at?->format('d/m/Y') ?? '-'),

                    Forms\Components\Placeholder::make('deadline_band')
                        ->label('Urgensi')
                        ->content(fn (ProductionWorkOrder $record) => $record->deadline_band?->value ?? '-'),
                ])
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ]),

            \Filament\Schemas\Components\Section::make('Status Produksi')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(ProgressStatus::class)
                        ->required(),

                    Forms\Components\Select::make('current_stage_id')
                        ->label('Tahap Saat Ini')
                        ->relationship('currentStage', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('assigned_personnel_id')
                        ->label('Petugas')
                        ->relationship('assignedPersonnel', 'full_name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('remaining_steps')
                        ->label('Sisa Langkah')
                        ->numeric(),
                ])
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ]),

            \Filament\Schemas\Components\Section::make('Hold / Blokir')
                ->schema([
                    Forms\Components\Toggle::make('is_held')
                        ->label('Ditahan (On Hold)')
                        ->live(),

                    Forms\Components\TextInput::make('hold_reason')
                        ->label('Alasan Hold')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('is_held')),

                    Forms\Components\Toggle::make('dependencies_met')
                        ->label('Dependensi Terpenuhi'),

                    Forms\Components\TextInput::make('blocked_reason')
                        ->label('Alasan Blokir'),
                ])
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ])
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('order.order_code')
                    ->label('Kode Pesanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('order.account.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('order.product_sentence')
                    ->label('Produk')
                    ->limit(35),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        if ($record->status?->value === 'DONE' || $record->status?->value === 'COMPLETED') return 100;
                        if (!$record->estimated_minutes) return 0;
                        $progress = max(0, min(100, (($record->estimated_minutes - $record->remaining_minutes) / max(1, $record->estimated_minutes)) * 100));
                        return round($progress);
                    })
                    ->formatStateUsing(function ($state) {
                        $width = $state . '%';
                        return new \Illuminate\Support\HtmlString('
                            <div class="flex items-center gap-2 min-w-[100px]">
                                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 overflow-hidden">
                                    <div class="bg-primary-500 h-2 rounded-full" style="width: '.$width.'"></div>
                                </div>
                                <span class="text-xs text-muted-foreground whitespace-nowrap tabular-nums">'.$state.'%</span>
                            </div>
                        ');
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? (string)$state) {
                        'NOT_STARTED' => 'gray',
                        'STARTED'     => 'info',
                        'BLOCKED'     => 'danger',
                        'REWORK'      => 'warning',
                        'COMPLETED', 'DONE' => 'success',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('deadline_band')
                    ->label('Urgensi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state?->value ?? (string)$state) {
                        'SAFE'      => '🟢 Aman',
                        'H3'        => '🟡 H-3',
                        'DUE_TODAY' => '🟠 Hari Ini',
                        'OVERDUE'   => '🔴 Terlambat',
                        'DONE'      => '✅ Selesai',
                        default     => '-',
                    })
                    ->color(fn ($state): string => match ($state?->value ?? (string)$state) {
                        'SAFE'      => 'success',
                        'H3'        => 'warning',
                        'DUE_TODAY' => 'danger',
                        'OVERDUE'   => 'danger',
                        'DONE'      => 'gray',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('currentStage.name')
                    ->label('Tahap')
                    ->default('-'),

                Tables\Columns\TextColumn::make('assignedPersonnel.full_name')
                    ->label('Petugas')
                    ->default('-'),

                Tables\Columns\IconColumn::make('is_held')
                    ->label('Hold')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('Pin')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('order.deadline_at')
                    ->label('Deadline')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ProgressStatus::class),

                Tables\Filters\SelectFilter::make('deadline_band')
                    ->label('Urgensi')
                    ->options([
                        'OVERDUE'   => '🔴 Terlambat',
                        'DUE_TODAY' => '🟠 Hari Ini',
                        'H3'        => '🟡 H-3',
                        'SAFE'      => '🟢 Aman',
                    ]),

                Tables\Filters\TernaryFilter::make('is_held')
                    ->label('Hold'),

                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Dipinned'),
            ])
            ->actions([
                \Filament\Actions\Action::make('log_progress')
                    ->label('Log Progres')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ProgressStatus::class)
                            ->required(),

                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->rows(2),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Mulai Pada'),
                    ])
                    ->action(function (ProductionWorkOrder $record, array $data) {
                        try {
                            app(ProductionService::class)->addProgressLog(
                                $record->id,
                                $data,
                                auth()->id()
                            );
                            Notification::make()->title('Log progres dicatat.')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                \Filament\Actions\Action::make('hold')
                    ->label('Toggle Hold')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Toggle::make('is_held')
                            ->label('Tahan Pesanan')
                            ->default(fn (ProductionWorkOrder $record) => !$record->is_held),

                        Forms\Components\Textarea::make('hold_reason')
                            ->label('Alasan Hold')
                            ->rows(2),
                    ])
                    ->visible(fn () => auth()->user()?->hasAnyRole(['MANAGER', 'SUPER_ADMIN']))
                    ->action(function (ProductionWorkOrder $record, array $data) {
                        try {
                            app(ProductionService::class)->holdJob(
                                $record->id,
                                $data['is_held'],
                                $data['hold_reason'] ?? null,
                                auth()->id()
                            );
                            Notification::make()->title('Status hold diperbarui.')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                \Filament\Actions\EditAction::make()->label('Update'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionWorkOrders::route('/'),
            'edit'  => Pages\EditProductionWorkOrder::route('/{record}/edit'),
        ];
    }
}
