<?php

namespace App\Filament\Resources\OrderResource\Schema;

use App\Enums\DesignStatus;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Product;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;

class OrderFormSchema
{
    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

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
        $model   = $get('product_model_id') ?? '';

        $extras = implode('/', array_filter([$shape, $variant, $color]));

        $parts = array_filter([
            $accountName ?: null,
            $productName ?: null,
            $model       ?: null,
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

    /**
     * Reset semua field produk ke null (dipanggil saat Jenis Produk berubah).
     */
    public static function resetProductFields(Set $set): void
    {
        $set('product_model_id', null);
        $set('_size_p', null);
        $set('_size_l', null);
        $set('_text', null);
        $set('_color', null);
        $set('_variant', null);
        $set('_shape', null);
        $set('_bracket', null);
        $set('_lamp', null);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Schema
    // ────────────────────────────────────────────────────────────────────────

    public static function getSchema(): array
    {
        return [
            Section::make()
                ->columnSpan('full')
                ->schema([

                    // ── Row 1: Timestamp | Admin ──────────────────────────────────
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

                    // ── Row 2: Order By | Nama Akun ───────────────────────────────
                    Forms\Components\Select::make('order_source_name')
                        ->label('Order by')
                        ->placeholder('Pilih platform...')
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
                                $set('_temp_account_name', \App\Models\CustomerAccount::find($state)?->name);
                            } else {
                                $set('_temp_account_name', null);
                            }
                            static::updateSentence($get, $set);
                        }),

                    Forms\Components\Hidden::make('_temp_account_name'),

                    // ── Jenis Produk (full-width, memicu perubahan form) ──────────
                    Forms\Components\Select::make('product_type_name')
                        ->label('Jenis Produk')
                        ->options([
                            'Advertising 1' => 'Advertising 1 — NeonBox',
                            'Advertising 2' => 'Advertising 2 — NeonFlex / Letter 3D / Akrilik',
                            'Home Decor'    => 'Home Decor',
                            'Logo & Ukir'   => 'Logo & Tulisan Ukir',
                        ])
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live()
                        ->columnSpanFull()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            // Auto-isi NeonBox untuk Advertising 1
                            $set('product_name', $state === 'Advertising 1' ? 'NeonBox' : null);
                            static::resetProductFields($set);
                            static::updateSentence($get, $set);
                        }),

                    // ════════════════════════════════════════════════════════════
                    // FIELD PRODUK — 3 varian, hanya satu yang tampil sesuai tipe
                    // ════════════════════════════════════════════════════════════

                    // Advertising 1 → TextInput disabled, otomatis "NeonBox"
                    Forms\Components\TextInput::make('product_name')
                        ->label('Produk')
                        ->disabled()
                        ->dehydrated()
                        ->default('NeonBox')
                        ->hint('Otomatis diisi sesuai jenis produk.')
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Advertising 1'),

                    // Advertising 2 → Dropdown pilihan produk
                    Forms\Components\Select::make('product_name')
                        ->label('Produk')
                        ->placeholder('Pilih produk...')
                        ->options([
                            'NeonFlex'             => 'NeonFlex',
                            'Letter 3D'            => 'Letter 3D',
                            'Logo Akrilik'         => 'Logo Akrilik',
                            'Tulisan Akrilik Spon' => 'Tulisan Akrilik Spon',
                            'Custom'               => 'Custom',
                        ])
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Advertising 2'),

                    // Home Decor & Logo & Ukir → TextInput bebas + datalist
                    Forms\Components\TextInput::make('product_name')
                        ->label('Produk')
                        ->datalist(fn (Get $get) => Product::where('is_active', true)
                            ->when($get('product_type_name'), function ($q, $v) {
                                $q->whereHas('productType', fn ($q2) => $q2->where('name', $v));
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
                            static::resetProductFields($set);
                            static::updateSentence($get, $set);
                        })
                        ->disabled(fn (Get $get) => !$get('product_type_name'))
                        ->hint(fn (Get $get) => !$get('product_type_name') ? 'Pilih Jenis Produk dahulu.' : null)
                        ->visible(fn (Get $get) => !in_array($get('product_type_name'), ['Advertising 1', 'Advertising 2'])),

                    // ════════════════════════════════════════════════════════════
                    // FIELD KONDISIONAL — Advertising 1
                    // ════════════════════════════════════════════════════════════

                    Forms\Components\Select::make('_shape')
                        ->label('Bentuk')
                        ->placeholder('Pilih opsi')
                        ->options([
                            'Bulat'  => 'Bulat',
                            'Kotak'  => 'Kotak',
                            'Custom' => 'Custom',
                        ])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Advertising 1'),

                    Forms\Components\Select::make('_variant')
                        ->label('Varian/Model')
                        ->placeholder('Pilih opsi')
                        ->options([
                            '1 sisi' => '1 Sisi',
                            '2 sisi' => '2 Sisi',
                        ])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Advertising 1'),

                    // ════════════════════════════════════════════════════════════
                    // FIELD KONDISIONAL — Logo & Tulisan Ukir
                    // ════════════════════════════════════════════════════════════

                    Forms\Components\Select::make('product_model_id')
                        ->label('Model')
                        ->placeholder('Pilih opsi')
                        ->options([
                            'Model 1'    => '1',
                            'Model 2'    => '2',
                            'Model 3'    => '3',
                            'Model Alur' => 'Alur',
                        ])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Logo & Ukir'),

                    Forms\Components\Select::make('_lamp')
                        ->label('Lampu')
                        ->placeholder('Pilih opsi')
                        ->options([
                            'Tanpa Lampu' => 'Tanpa Lampu',
                            'Lampu'       => 'Lampu',
                        ])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Logo & Ukir'),

                    Forms\Components\TextInput::make('_color')
                        ->label('Warna Tulisan')
                        ->placeholder('Merah, Biru, Gold...')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Logo & Ukir'),

                    // ════════════════════════════════════════════════════════════
                    // UKURAN — Adv1, Adv2, Logo & Ukir
                    // ════════════════════════════════════════════════════════════

                    \Filament\Schemas\Components\Group::make([
                        Forms\Components\TextInput::make('_size_p')
                            ->label('Ukuran (CM) — Panjang')
                            ->numeric()
                            ->placeholder('Panjang')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                            ->suffix('x'),

                        Forms\Components\TextInput::make('_size_l')
                            ->label('Lebar / Tinggi')
                            ->numeric()
                            ->placeholder('Lebar / Tinggi')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                            ->hint('Format: PxL cm'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => in_array(
                        $get('product_type_name'),
                        ['Advertising 1', 'Advertising 2', 'Logo & Ukir']
                    )),

                    // ════════════════════════════════════════════════════════════
                    // Advertising 1 — Bracket
                    // ════════════════════════════════════════════════════════════

                    Forms\Components\Select::make('_bracket')
                        ->label('Bracket')
                        ->placeholder('Pilih opsi')
                        ->options([
                            'Bawah'   => 'Bawah',
                            'Samping' => 'Samping',
                            'Custom'  => 'Custom',
                        ])
                        ->live(debounce: 0)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::updateSentence($get, $set))
                        ->visible(fn (Get $get) => $get('product_type_name') === 'Advertising 1'),

                    // ════════════════════════════════════════════════════════════
                    // CATATAN CS — Adv1, Adv2, Logo & Ukir
                    // ════════════════════════════════════════════════════════════

                    Forms\Components\Textarea::make('_special_requests')
                        ->label('Catatan CS')
                        ->rows(2)
                        ->placeholder('Catatan tambahan untuk desainer / produksi...')
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => in_array(
                            $get('product_type_name'),
                            ['Advertising 1', 'Advertising 2', 'Logo & Ukir']
                        )),

                    // ════════════════════════════════════════════════════════════
                    // SECTION BAWAH — selalu tampil
                    // ════════════════════════════════════════════════════════════

                    // Deadline | Daerah
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
                                $days = (int) now()->startOfDay()->diffInDays(
                                    \Carbon\Carbon::parse($deadline)->startOfDay(), false
                                );
                                if ($days < 0)  return '🔴 Deadline sudah lewat!';
                                if ($days === 0) return '🟠 Deadline HARI INI!';
                                if ($days <= 3)  return '🟡 H-' . $days . ' — segera!';
                            } catch (\Exception $e) {}
                            return null;
                        }),

                    Forms\Components\Select::make('city')
                        ->label('Daerah Tujuan')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return \App\Services\EmsifaService::search($search);
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => $value)
                        ->allowHtml(false)
                        ->placeholder('Ketik nama kecamatan, kota...'),

                    // Ekspedisi | Packing
                    Forms\Components\Select::make('expedition_id')
                        ->label('Ekspedisi')
                        ->placeholder('Pilih ekspedisi...')
                        ->options(fn () => \App\Models\Carrier::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray())
                        ->required()
                        ->validationMessages(['required' => ' '])
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('packing_type')
                        ->label('Jenis Packing')
                        ->placeholder('Pilih opsi')
                        ->options(PackingType::class)
                        ->required()
                        ->validationMessages(['required' => ' ']),

                    // Payment Type | Payment Status
                    Forms\Components\Select::make('payment_type')
                        ->label('Tipe Pembayaran')
                        ->placeholder('Pilih opsi')
                        ->options(PaymentType::class)
                        ->required()
                        ->validationMessages(['required' => ' ']),

                    Forms\Components\Select::make('payment_status')
                        ->label('Status Pembayaran')
                        ->placeholder('Pilih opsi')
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

                    // Total | Jumlah Dibayar
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

                    // Status | Design Status
                    Forms\Components\Select::make('status')
                        ->label('Status Pesanan')
                        ->placeholder('Pilih opsi')
                        ->options([
                            'DRAFT'     => 'Draft',
                            'CONFIRMED' => 'Confirmed',
                            'ON_HOLD'   => 'On-hold',
                        ])
                        ->default('DRAFT')
                        ->required(),

                    Forms\Components\Select::make('design_status')
                        ->label('Status Desain')
                        ->placeholder('Pilih opsi')
                        ->options(DesignStatus::class)
                        ->required(),

                    // Catatan Produksi & Link (selalu tampil, untuk internal)
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

                    // ── Preview Kalimat Pesanan ───────────────────────────────
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
