<?php

namespace App\Filament\Resources;

use App\Enums\DesignTaskStatus;
use App\Filament\Resources\DesignTaskResource\Pages;
use App\Models\DesignTask;
use App\Services\DesignService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DesignTaskResource extends Resource
{
    protected static ?string $model = DesignTask::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Tugas Desain';

    protected static ?string $pluralModelLabel = 'Design';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-paint-brush';
    }

    public static function getNavigationLabel(): string
    {
        return 'Design';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Desainer';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['DESIGNER', 'SUPER_ADMIN', 'DEVELOPER']);
    }

    // ─── Form (halaman Edit) ──────────────────────────────────────────────

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Detail Pesanan')
                ->schema([
                    Forms\Components\Placeholder::make('order_code')
                        ->label('Kode Pesanan')
                        ->content(fn (DesignTask $record) => $record->order?->order_code ?? '-'),

                    Forms\Components\Placeholder::make('order_created_at')
                        ->label('Dibuat')
                        ->content(fn (DesignTask $record) =>
                            $record->order?->created_at?->format('d/m/Y H:i') ?? '-'),

                    Forms\Components\Placeholder::make('product_sentence')
                        ->label('Deskripsi Produk')
                        ->content(fn (DesignTask $record) => $record->order?->product_sentence ?? '-'),

                    Forms\Components\Placeholder::make('deadline_at')
                        ->label('Deadline')
                        ->content(fn (DesignTask $record) =>
                            $record->order?->deadline_at?->format('d/m/Y H:i') ?? '-'),
                ])
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 4]),

            \Filament\Schemas\Components\Section::make('Update Desain')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status Desain')
                        ->options(DesignTaskStatus::class)
                        ->required(),

                    Forms\Components\Select::make('assigned_designer_id')
                        ->label('Desainer')
                        ->relationship('assignedDesigner', 'full_name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('print_sticker')
                        ->label('Print Sticker')
                        ->options([
                            'YES'            => 'Ya',
                            'NO'             => 'Tidak',
                            'REQUIRED_LATER' => 'Nanti',
                        ]),

                    Forms\Components\DateTimePicker::make('design_acc_at')
                        ->label('Waktu ACC')
                        ->placeholder('Belum di-ACC oleh CS'),
                ])
                ->columns(['default' => 1, 'sm' => 2]),
        ]);
    }

    // ─── Table (Antrian Desain) ───────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([

                // ── Kode Pesanan + tanggal pesanan dibuat ─────────────────────────
                Tables\Columns\TextColumn::make('order.order_code')
                    ->label('Pesanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        $record->order?->created_at?->format('d/m/Y H:i') ?? '-'
                    ),

                // ── Desainer ──────────────────────────────────────────────────────
                Tables\Columns\SelectColumn::make('assigned_designer_id')
                    ->label('Desainer')
                    ->options(fn () => \App\Models\Personnel::where('division', 'Design')
                        ->where('is_active', true)
                        ->pluck('full_name', 'id')
                        ->toArray()
                    )
                    ->placeholder('Belum Diambil')
                    ->searchable()
                    ->sortable(),

                // ── Deskripsi produk ──────────────────────────────────────────────
                Tables\Columns\TextColumn::make('order.product_sentence')
                    ->label('Produk')
                    ->searchable()
                    ->limit(40),

                // ── Deadline ──────────────────────────────────────────────────────
                Tables\Columns\TextColumn::make('order.deadline_at')
                    ->label('Deadline')
                    ->sortable()
                    ->formatStateUsing(function ($state): string {
                        if (!$state) return '-';
                        try {
                            $days = (int) now()->startOfDay()
                                ->diffInDays(Carbon::parse($state)->startOfDay(), false);
                            if ($days < 0)  return '🔴 Lewat ' . abs($days) . ' hr';
                            if ($days === 0) return '🟠 HARI INI';
                            return '🟡 H-' . $days;
                        } catch (\Exception) {
                            return '-';
                        }
                    })
                    ->badge()
                    ->color(function ($state): string {
                        if (!$state) return 'gray';
                        try {
                            $days = (int) now()->startOfDay()
                                ->diffInDays(Carbon::parse($state)->startOfDay(), false);
                            if ($days < 0)  return 'danger';
                            if ($days <= 2) return 'warning';
                            return 'success';
                        } catch (\Exception) {
                            return 'gray';
                        }
                    }),

                // ── Customer ──────────────────────────────────────────────────────
                Tables\Columns\TextColumn::make('order.account.name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Asal Orderan — badge berwarna per platform ────────────────────
                // Inline CSS: 100% kompatibel dengan semua versi Chrome.
                Tables\Columns\TextColumn::make('order.orderSource.name')
                    ->label('Asal Orderan')
                    ->html()
                    ->formatStateUsing(function ($state): string {
                        $name  = (string) $state;
                        $lower = strtolower($name);
                        $base  = 'display:inline-block;padding:2px 10px;border-radius:9999px;'
                            . 'font-size:0.72rem;font-weight:700;white-space:nowrap;';

                        $style = match (true) {
                            str_contains($lower, 'shopee')
                                => $base . 'background-color:#ee4d2d;color:#ffffff;',
                            str_contains($lower, 'tiktok')
                                => $base . 'background-color:#010101;color:#ffffff;',
                            str_contains($lower, 'whatsapp') || $lower === 'wa' || str_starts_with($lower, 'wa ')
                                => $base . 'background-color:#25D366;color:#ffffff;',
                            default
                                => $base . 'background-color:#6b7280;color:#ffffff;',
                        };

                        return '<span style="' . $style . '">' . e($name) . '</span>';
                    }),

                // ── Tanggal ACC — klik → popup DateTimePicker kecil ───────────────
                //
                // BUSINESS LOGIC:
                //  • CS pilih ACC (design_status=ACC) → design_acc_at terisi
                //    otomatis saat CS simpan (via upsertDesignTask / OrderService).
                //  • CS pilih PROCESS → design_acc_at = null → designer melihat "—".
                //  • Designer tetap bisa override/edit dengan klik langsung di sini.
                //  • DateTimePicker default ke now() jika belum ada nilai.
                //
                Tables\Columns\TextColumn::make('design_acc_at')
                    ->label('Tgl ACC')
                    ->placeholder('—')
                    ->formatStateUsing(function ($state): string {
                        if (blank($state)) return '—';
                        try {
                            return Carbon::parse($state)->format('d/m/Y H:i');
                        } catch (\Exception) {
                            return '—';
                        }
                    })
                    ->action(
                        \Filament\Actions\Action::make('editAccDate')
                            ->label('Set Tanggal ACC')
                            ->icon('heroicon-o-calendar-days')
                            ->color('warning')
                            ->modalWidth('sm')
                            ->form([
                                Forms\Components\DatePicker::make('design_acc_date')
                                    ->label('Tanggal ACC')
                                    ->required(),
                            ])
                            ->fillForm(function (DesignTask $record): array {
                                $raw = $record->getRawOriginal('design_acc_at');
                                return [
                                    'design_acc_date' => filled($raw)
                                        ? Carbon::parse($raw)->format('Y-m-d')
                                        : now()->format('Y-m-d'),
                                ];
                            })
                            ->action(function (DesignTask $record, array $data): void {
                                $time = now()->format('H:i:s');
                                $newDatetime = $data['design_acc_date'] . ' ' . $time;
                                $record->update([
                                    'design_acc_at' => $newDatetime,
                                ]);
                            })
                    ),

                // ── Print Sticker — inline select, langsung diedit di baris ───────
                // print_sticker disimpan sebagai plain string tanpa enum cast
                // sehingga SelectColumn dapat membandingkan state vs. option keys.
                Tables\Columns\SelectColumn::make('print_sticker')
                    ->label('Print Sticker')
                    ->options([
                        'YES'            => '✅ Ya',
                        'NO'             => '❌ Tidak',
                    ])
                    ->selectablePlaceholder(false),

                // ── CNC / Laser — checkbox list via action popup ───────────────────
                // Menampilkan metode yang sudah dipilih, klik → popup checkbox.
                // Mendukung multi-pilih (CNC + Laser sekaligus).
                Tables\Columns\TextColumn::make('cut_methods')
                    ->label('CNC / Laser')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) return 'Belum diset';
                        $labels = ['CNC' => 'CNC', 'LASER' => 'Laser'];
                        $items = is_array($state) ? $state : [$state];
                        return implode(', ', array_map(fn ($m) => $labels[$m] ?? $m, $items));
                    })
                    ->color(fn ($state) => empty($state) ? 'gray' : 'info')
                    ->action(
                        \Filament\Actions\Action::make('setCutMethods')
                            ->label('Pilih Metode Potong')
                            ->icon('heroicon-o-scissors')
                            ->color('gray')
                            ->modalWidth('xs')
                            ->form([
                                Forms\Components\CheckboxList::make('cut_methods')
                                    ->label('Centang semua yang berlaku:')
                                    ->options([
                                        'CNC'       => '🔵 CNC',
                                        'LASER'     => '🔴 Laser',
                                    ])
                                    ->columns(1),
                            ])
                            ->fillForm(fn (DesignTask $record): array => [
                                'cut_methods' => $record->cut_methods ?? [],
                            ])
                            ->action(function (DesignTask $record, array $data): void {
                                $record->update([
                                    'cut_methods' => array_values(array_unique($data['cut_methods'] ?? [])),
                                ]);
                            })
                    ),

                // ── Status Select ──────────────────────────────────────────────────
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'PROCESS' => 'Proses',
                        'ACC'     => '✅ ACC',
                    ])
                    ->selectablePlaceholder(false)
                    ->updateStateUsing(function ($record, $state) {
                        $record->update([
                            'status'        => $state,
                            'design_acc_at' => $state === 'ACC' ? now() : null,
                        ]);
                        return $state;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PROCESS' => 'Proses',
                        'ACC'     => 'ACC',
                    ]),

                Tables\Filters\SelectFilter::make('order_source')
                    ->label('Asal Orderan')
                    ->options(fn () => \App\Models\OrderSource::pluck('name', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('order', fn ($q) =>
                                $q->where('order_source_id', $data['value'])
                            );
                        }
                    }),
            ])
            ->actions([



                // ── Catatan CS — slide-over dari kanan ────────────────────────────
                \Filament\Actions\Action::make('catatanAdmin')
                    ->label('📋 Catatan')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->button()
                    ->slideOver()
                    ->modalHeading('Catatan CS & Detail Pesanan')
                    ->modalContent(fn (DesignTask $record) =>
                        view('filament.pages.design-task-notes', ['record' => $record])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Tutup')),

                // ── Teruskan ke Produksi ──────────────────────────────────────────
                \Filament\Actions\Action::make('forward')
                    ->label(fn (DesignTask $record) =>
                        $record->forwarded_at ? '✔ Sudah di Produksi' : '→ Ke Produksi'
                    )
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color(fn (DesignTask $record) => $record->forwarded_at ? 'gray' : 'primary')
                    ->button()
                    ->requiresConfirmation(fn (DesignTask $record) => !$record->forwarded_at)
                    ->modalHeading('Forward ke Produksi?')
                    ->modalDescription('Desain pesanan ini akan diteruskan ke antrian produksi.')
                    ->visible(fn (DesignTask $record) => $record->status === DesignTaskStatus::ACC)
                    ->disabled(fn (DesignTask $record) => $record->forwarded_at !== null)
                    ->action(function (DesignTask $record) {
                        try {
                            app(DesignService::class)->forwardToProduction($record->id, auth()->id());
                            Notification::make()->title('Pesanan berhasil diteruskan ke produksi.')
                                ->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesignTasks::route('/'),
        ];
    }
}
