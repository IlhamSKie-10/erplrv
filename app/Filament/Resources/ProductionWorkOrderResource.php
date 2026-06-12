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
        return $user && $user->hasAnyRole(['CS', 'DESIGNER', 'PRODUCTION', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['PRODUCTION', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['PRODUCTION', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']) ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['PRODUCTION', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']) ?? false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([



        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNotIn('status', [ProgressStatus::COMPLETED, ProgressStatus::DONE]))
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
                    ->extraAttributes(['style' => 'min-width: 350px; max-width: 350px; white-space: normal;'])
                    ->wrap(),

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



                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Dipinned'),
            ])
            ->actions([
                \Filament\Actions\Action::make('log_progress')
                    ->label('Log Progres')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['PRODUCTION', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']))
                    ->form([
                        Forms\Components\Select::make('stage_id')
                            ->label('Tahap Saat Ini')
                            ->options(\App\Models\ProductionStage::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (ProductionWorkOrder $record) => $record->current_stage_id),

                        Forms\Components\Select::make('personnel_id')
                            ->label('Petugas')
                            ->options(\App\Models\Personnel::where('division', 'Production')->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (ProductionWorkOrder $record) => $record->assigned_personnel_id),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'STARTED' => 'Mulai',
                                'COMPLETED' => 'Selesai',
                            ])
                            ->required()
                            ->default(fn (ProductionWorkOrder $record) => $record->status),

                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->rows(2),

                        Forms\Components\Placeholder::make('started_at_display')
                            ->label('Mulai Pada')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div x-data="{
                                    ts: ' . (now()->timestamp * 1000) . ',
                                    time: \'\',
                                    format() {
                                        let d = new Date(this.ts);
                                        let months = [\'Januari\',\'Februari\',\'Maret\',\'April\',\'Mei\',\'Juni\',\'Juli\',\'Agustus\',\'September\',\'Oktober\',\'November\',\'Desember\'];
                                        let day = String(d.getDate()).padStart(2, \'0\');
                                        let month = months[d.getMonth()];
                                        let year = d.getFullYear();
                                        let hour = String(d.getHours()).padStart(2, \'0\');
                                        let min = String(d.getMinutes()).padStart(2, \'0\');
                                        let sec = String(d.getSeconds()).padStart(2, \'0\');
                                        return day + \' \' + month + \' \' + year + \', \' + hour + \':\' + min + \':\' + sec + \' WIB\';
                                    }
                                }" x-init="time = format(); setInterval(() => { ts += 1000; time = format(); }, 1000)">
                                    <span x-text="time" class="text-sm font-medium"></span>
                                </div>
                            ')),
                    ])
                    ->action(function (ProductionWorkOrder $record, array $data) {
                        try {
                            // Force realtime
                            $data['started_at'] = now();
                            
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



                \Filament\Actions\EditAction::make()->label('Detail Progres'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ProductionWorkOrderResource\RelationManagers\ProgressLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionWorkOrders::route('/'),
            'edit'  => Pages\EditProductionWorkOrder::route('/{record}/edit'),
        ];
    }
}
