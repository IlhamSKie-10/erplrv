<?php

namespace App\Filament\Resources;

use App\Enums\DesignStatus;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\Support\IndonesianCities;
use App\Filament\Resources\Support\ProductCatalog;
use App\Models\CustomerAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Services\OrderService;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pesanan';

    protected static ?string $pluralModelLabel = 'Daftar Pesanan';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationLabel(): string
    {
        return 'All Order';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Customer Service';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['CS', 'DESIGNER', 'PRODUCTION', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['CS', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['CS', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']) ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['CS', 'SUPER_ADMIN', 'MANAGER', 'DEVELOPER']) ?? false;
    }

    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema(\App\Filament\Resources\OrderResource\Schema\OrderFormSchema::getSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_code')
                    ->label('Kode Pesanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Kode disalin!'),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_sentence')
                    ->label('Kalimat Pesanan')
                    ->tooltip(fn ($record) => $record->product_sentence)
                    ->extraAttributes(['style' => 'min-width: 350px; max-width: 350px; white-space: normal;'])
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? (string)$state) {
                        'DRAFT'              => 'gray',
                        'CONFIRMED'          => 'info',
                        'DESIGN_IN_PROGRESS' => 'warning',
                        'DESIGN_APPROVED'    => 'primary',
                        'IN_PRODUCTION'      => 'warning',
                        'READY_TO_SHIP'      => 'info',
                        'SHIPPED'            => 'primary',
                        'COMPLETED'          => 'success',
                        'CANCELLED'          => 'danger',
                        'ON_HOLD'            => 'warning',
                        default              => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Bayar')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? (string)$state) {
                        'UNPAID' => 'danger',
                        'DP'     => 'warning',
                        'LUNAS'  => 'success',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_order')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('deadline_at')
                    ->label('Deadline')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        $record->deadline_at?->isPast()                            => 'danger',
                        $record->deadline_at?->isToday()                           => 'danger',
                        $record->deadline_at?->diffInDays(now(), false) >= -3      => 'warning',
                        default                                                    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('createdBy.full_name')
                    ->label('Admin')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(OrderStatus::class),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Bayar')
                    ->options(PaymentStatus::class),

                Tables\Filters\Filter::make('overdue')
                    ->label('Deadline Terlewat')
                    ->query(fn (Builder $query) => $query->where('deadline_at', '<', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->label('Periode Pesanan')
                    ->form([
                        Forms\Components\Select::make('range')
                            ->label('Pilih Periode')
                            ->options([
                                'today' => 'Hari Ini',
                                'this_week' => 'Minggu Ini',
                                'this_month' => 'Bulan Ini',
                                'custom' => 'Pilih Tanggal',
                            ])
                            ->live(),
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('range') === 'custom'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('range') === 'custom'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $range = $data['range'] ?? null;
                        return $query
                            ->when(
                                $range === 'today',
                                fn (Builder $q) => $q->whereDate('created_at', now()->toDateString())
                            )
                            ->when(
                                $range === 'this_week',
                                fn (Builder $q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                            )
                            ->when(
                                $range === 'this_month',
                                fn (Builder $q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
                            )
                            ->when(
                                $range === 'custom',
                                fn (Builder $q) => $q
                                    ->when(
                                        $data['from'] ?? null,
                                        fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date)
                                    )
                                    ->when(
                                        $data['until'] ?? null,
                                        fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)
                                    )
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $range = $data['range'] ?? null;
                        if ($range === 'today') return 'Periode: Hari Ini';
                        if ($range === 'this_week') return 'Periode: Minggu Ini';
                        if ($range === 'this_month') return 'Periode: Bulan Ini';
                        if ($range === 'custom') {
                            $from = $data['from'] ?? null;
                            $until = $data['until'] ?? null;
                            if ($from && $until) {
                                return 'Periode: ' . \Carbon\Carbon::parse($from)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($until)->format('d/m/Y');
                            }
                            if ($from) return 'Sejak: ' . \Carbon\Carbon::parse($from)->format('d/m/Y');
                            if ($until) return 'Sampai: ' . \Carbon\Carbon::parse($until)->format('d/m/Y');
                        }
                        return null;
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('submit')
                    ->label('Submit ke Desain')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Pesanan ke Desain?')
                    ->modalDescription('Pesanan akan dikirim ke antrian desainer. Pastikan semua data sudah benar.')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::DRAFT)
                    ->action(function (Order $record) {
                        try {
                            app(OrderService::class)->submitOrder($record->id, auth()->id());
                            Notification::make()->title('Pesanan berhasil disubmit ke Desain.')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                \Filament\Actions\EditAction::make()
                    ->visible(fn (Order $record) => $record->status === OrderStatus::DRAFT),

                \Filament\Actions\Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::DRAFT, OrderStatus::CONFIRMED]))
                    ->action(function (Order $record) {
                        try {
                            app(OrderService::class)->duplicateOrder($record->id, auth()->id());
                            Notification::make()->title('Pesanan berhasil diduplikat sebagai draft baru.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal menduplikat pesanan.')->danger()->send();
                        }
                    }),

                \Filament\Actions\Action::make('soft_delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->status === OrderStatus::DRAFT)
                    ->action(function (Order $record) {
                        app(OrderService::class)->softDelete($record->id, auth()->id());
                        Notification::make()->title('Pesanan berhasil dihapus.')->success()->send();
                    }),

                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view'   => Pages\ViewOrder::route('/{record}'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('deleted_at');
    }
}
