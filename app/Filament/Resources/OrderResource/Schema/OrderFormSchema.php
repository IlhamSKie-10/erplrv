<?php

namespace App\Filament\Resources\OrderResource\Schema;

use App\Enums\DesignStatus;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Filament\Resources\Support\ProductCatalog;
use App\Models\CustomerAccount;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\ProductType;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;

class OrderFormSchema
{
    public static function buildProductSentence(Get $get): string
    {
        $productName = $get('product_name') ?? '';
        $accountName = $get('_temp_account_name') ?? $get('account_name') ?? '';
        $size        = $get('_size_p') && $get('_size_l')
                       ? $get('_size_p') . 'x' . $get('_size_l') . ' cm'
                       : '';
        $text    = $get('_text') ?? '';
        $color   = $get('_color') ?? '';
        $shape   = $get('_shape') ?? '';
        $variant = $get('_variant') ?? '';
        $lamp    = $get('_lamp') ?? '';
        $bracket = $get('_bracket') ?? '';

        $extras = implode('/', array_filter([$color, $shape, $variant]));

        $parts = array_filter([
            $accountName ?: null,
            $productName ?: null,
            $size        ?: null,
            $text        ?: null,
            $extras      ?: null,
            $lamp        ?: null,
            $bracket     ?: null,
        ]);

        return implode(' - ', $parts) ?: '-';
    }

    public static function updateSentence(Get $get, Set $set): void
    {
        $set('product_sentence', static::buildProductSentence($get));
    }

    public static function getSchema(): array
    {
        return [
            Section::make()
                ->columnSpan('full')
                ->schema([

                    // ── Row 1: Timestamp | Admin ──────────────────────────
                    Forms\Components\Placeholder::make('waktu_order')
                        ->label('Timestamp (Real-Time)')
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
                                <span x-text="time"></span>
                            </div>
                        ')),

                    Forms\Components\Placeholder::make('_admin_label')
                        ->label('Admin')
                        ->content(fn () => auth()->user()?->getFilamentName() ?? '-'),

                    // ── Row 2: Order By | Nama Akun ────────────────────────
                    Forms\Components\Select::make('order_source_name')
                        ->selectablePlaceholder(false)
                        ->label('Order by')
                        ->options([
                            'What\'s apps' => 'What\'s apps',
                            'Tiktok'       => 'Tiktok',
                            'Shopee'       => 'Shopee',
                        ])
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set)),

                    Forms\Components\Select::make('account_id')
                        ->label('Nama Akun')
                        ->relationship('account', 'name')
                        ->searchable(['name', 'phone'])
                        ->preload()
                        ->placeholder('Ketik nama akun...')
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state) {
                                $accountName = \App\Models\CustomerAccount::find($state)?->name;
                                $set('_temp_account_name', $accountName);
                            } else {
                                $set('_temp_account_name', null);
                            }
                            static::updateSentence($get, $set);
                        }),
                    Forms\Components\Hidden::make('_temp_account_name'),

                    // ── Row 3: Jenis Produk | Produk ────────────────────────
                    Forms\Components\Select::make('product_type_name')
                        ->label('Jenis Produk')
                        ->options([
                            'Advertising 1' => 'Advertising 1',
                            'Advertising 2' => 'Advertising 2',
                            'Home Decor' => 'Homedecor',
                            'Logo & Ukir' => 'Logo & Tulisan Ukir',
                        ])
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $set('product_id', null);
                            $set('product_model_id', null);
                            $set('_size_p', null);
                            $set('_size_l', null);
                            $set('_text', null);
                            $set('_color', null);
                            $set('_variant', null);
                            $set('_shape', null);
                            $set('_bracket', null);
                            $set('_lamp', null);
                            static::updateSentence($get, $set);
                        }),

                    Forms\Components\TextInput::make('product_name')
                        ->label('Produk')
                        ->datalist(fn (Get $get) => Product::where('is_active', true)
                            ->when($get('product_type_name'), function ($q, $v) {
                                $q->whereHas('productType', fn($q2) => $q2->where('name', $v));
                            })
                            ->pluck('name')->toArray())
                        ->autocomplete(false)
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state) {
                                $formatted = ucwords(strtolower($state));
                                if ($state !== $formatted) {
                                    $set('product_name', $formatted);
                                }
                            }
                            $set('product_model_id', null);
                            $set('_size_p', null);
                            $set('_size_l', null);
                            $set('_text', null);
                            $set('_color', null);
                            $set('_variant', null);
                            $set('_shape', null);
                            $set('_bracket', null);
                            $set('_lamp', null);
                            static::updateSentence($get, $set);
                        })
                        ->disabled(fn (Get $get) => !$get('product_type_name'))
                        ->hint(fn (Get $get) => !$get('product_type_name') ? 'Pilih Jenis Produk dahulu.' : null),

                    // ── Row 4: Referensi Layout | Ukuran ─────────────────────
                    Forms\Components\Select::make('product_model_id')
                        ->selectablePlaceholder(false)
                        ->label('Referensi Layout')
                        ->options([
                            'Model 1'    => 'Model 1',
                            'Model 2'    => 'Model 2',
                            'Model 3'    => 'Model 3',
                            'Model Alur' => 'Model Alur',
                        ])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set)),

                    \Filament\Schemas\Components\Group::make([
                        Forms\Components\TextInput::make('P')
                            ->label('Ukuran (CM) - P')
                            ->numeric()
                            ->placeholder('Panjang')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                            ->suffix('x'),

                        Forms\Components\TextInput::make('L')
                            ->label(' ')
                            ->numeric()
                            ->placeholder('Lebar/Tinggi)')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                            ->hint('Otomatis diformat PxL'),
                    ])
                    ->columns(2),

                    // ── Spesifikasi opsional ───────────────────────────────
                    Forms\Components\TextInput::make('_text')
                        ->label('Tulisan')
                        ->placeholder('Nama toko, slogan...')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set)),

                    Forms\Components\TextInput::make('_color')
                        ->label('Warna')
                        ->placeholder('Merah, Biru, Custom...')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set)),

                    Forms\Components\Select::make('_variant')
                        ->selectablePlaceholder(false)
                        ->label('Varian')
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->options([
                            '1 sisi' => '1 Sisi',
                            '2 sisi' => '2 Sisi',
                        ]),

                    Forms\Components\Select::make('_bracket')
                        ->selectablePlaceholder(false)
                        ->label('Bracket')
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->options([
                            'Bawah'   => 'Bawah',
                            'Samping' => 'Samping',
                        ]),

                    Forms\Components\Select::make('_shape')
                        ->selectablePlaceholder(false)
                        ->label('Bentuk')
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->options([
                            'Bulat' => 'Bulat',
                            'Kotak' => 'Kotak',
                        ]),

                    Forms\Components\Select::make('_lamp')
                        ->selectablePlaceholder(false)
                        ->label('Lampu')
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->options([
                            'Tanpa Lampu' => 'Tanpa Lampu',
                            'Lampu'       => 'Lampu',
                        ]),

                    // ── Row: Deadline + Daerah ───────────────────────────
                    Forms\Components\DatePicker::make('deadline_at')
                        ->label('Deadline')
                        ->required()
                        ->minDate(now()->startOfDay())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live(onBlur: true)
                        ->hint(function (Get $get) {
                            $deadline = $get('deadline_at');
                            if (!$deadline) return null;
                            try {
                                $days = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($deadline)->startOfDay(), false);
                                if ($days < 0)  return '🔴 Deadline sudah lewat!';
                                if ($days === 0) return '🟠 Deadline HARI INI!';
                                if ($days <= 3)  return '🟡 H-' . $days . ' — segera!';
                            } catch (\Exception $e) {}
                            return null;
                        }),

                    Forms\Components\Select::make('city')
                        ->selectablePlaceholder(false)
                        ->label('Daerah Tujuan')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return \App\Services\EmsifaService::search($search);
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => $value)
                        ->allowHtml(false)
                        ->placeholder('Ketik nama kecamatan, kota...'),

                    // ── Row: Ekspedisi + Packing ─────────────────────────
                    Forms\Components\Select::make('expedition_id')
                        ->label('Ekspedisi')
                        ->relationship('expedition', 'name')
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->searchable(),

                    Forms\Components\Select::make('packing_type')
                        ->label('Jenis Packing')
                        ->options(PackingType::class)
                        ->required()
                        ->validationMessages(['required' => ' ']),

                    // ── Row: Payment ─────────────────────────────────────
                    Forms\Components\Select::make('payment_type')
                        ->label('Tipe Pembayaran')
                        ->options(PaymentType::class)
                        ->required()
                        ->validationMessages(['required' => ' ']),

                    Forms\Components\Select::make('payment_status')
                        ->label('Status Pembayaran')
                        ->options(PaymentStatus::class)
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state === PaymentStatus::LUNAS->value || $state === PaymentStatus::LUNAS) {
                                $set('amount_paid', $get('total_order') ?? 0);
                            } elseif ($state === PaymentStatus::UNPAID->value || $state === PaymentStatus::UNPAID) {
                                $set('amount_paid', 0);
                            }
                        }),

                    Forms\Components\TextInput::make('total_order')
                        ->label('Total Pesanan')
                        ->prefix('Rp')
                        ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\')'))
                        ->stripCharacters('.')
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if (
                                $get('payment_status') === PaymentStatus::LUNAS->value ||
                                $get('payment_status') === PaymentStatus::LUNAS
                            ) {
                                $set('amount_paid', $state ?? 0);
                            }
                        }),

                    Forms\Components\TextInput::make('amount_paid')
                        ->label('Jumlah Dibayar')
                        ->prefix('Rp')
                        ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\')'))
                        ->stripCharacters('.')
                        ->default(0)
                        ->live(onBlur: true)
                        ->disabled(fn (Get $get) => in_array($get('payment_status'), [
                            PaymentStatus::LUNAS->value, PaymentStatus::LUNAS,
                            PaymentStatus::UNPAID->value, PaymentStatus::UNPAID,
                        ], true))
                        ->dehydrated()
                        ->hint(fn (Get $get) => match (true) {
                            in_array($get('payment_status'), [PaymentStatus::LUNAS->value, PaymentStatus::LUNAS], true)  => '✅ Otomatis dari total.',
                            in_array($get('payment_status'), [PaymentStatus::UNPAID->value, PaymentStatus::UNPAID], true) => '⚠️ Dikunci: belum bayar.',
                            default => 'Isi nominal DP.',
                        }),

                    // ── Row: Status ──────────────────────────────────────
                    Forms\Components\Select::make('status')
                        ->label('Status Pesanan')
                        ->options([
                            'DRAFT'     => 'Draft',
                            'CONFIRMED' => 'Confirmed',
                            'ON_HOLD'   => 'On-hold',
                        ])
                        ->default('DRAFT')
                        ->required(),

                    Forms\Components\Select::make('design_status')
                        ->label('Status Desain')
                        ->options(DesignStatus::class)
                        ->required(),

                    // ── Catatan Tambahan (full width) ────────────────────
                    Forms\Components\Textarea::make('_production_notes')
                        ->label('Catatan Produksi')
                        ->rows(3)
                        ->placeholder('Contoh: Gunakan acrylic 5mm')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('_reference_link')
                        ->label('Link Referensi')
                        ->url()
                        ->placeholder('Contoh: https://drive.google.com/...')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('_special_requests')
                        ->label('Permintaan Khusus')
                        ->rows(3)
                        ->placeholder('Contoh: Mohon desain dibuat minimalis')
                        ->columnSpanFull(),

                    // ── Kalimat preview (full width) ─────────────────────
                    Forms\Components\Placeholder::make('product_sentence_preview')
                        ->label('📋 Format Kalimat Pesanan')
                        ->content(fn (Get $get) => static::buildProductSentence($get) ?: '—')
                        ->columnSpanFull()
                        ->hint('Kalimat ini otomatis dikirim ke desainer.'),

                    Forms\Components\Hidden::make('product_sentence'),
                ])
                ->columns(2),
        ];
    }
}
