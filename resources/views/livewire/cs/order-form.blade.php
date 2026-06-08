{{--
    CS Order Form — Livewire Component View
    Layout: 2-column side-by-side inside max-w-5xl modal.
    Left col: Info Dasar, Customer, Produk, Konfigurasi Produk
    Right col: Pengiriman, Pembayaran, Desain & Produksi
--}}

<div
    x-data="csOrderForm(@js($catalogConfig))"
    x-init="init()"
    @input="scheduleAutosave()"
    @change="scheduleAutosave()"
    class="space-y-3"
>

    {{-- ════════════════════════════════════════════
         STATUS BAR: Autosave indicator + timestamp
         ════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between gap-2 py-1">

        {{-- Autosave status --}}
        <div class="flex items-center gap-2 text-xs">
            @if ($errors->has('form') || $errors->has('version'))
                <span class="flex items-center gap-1.5 text-danger font-medium">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    {{ $errors->first('form') ?: $errors->first('version') }}
                </span>
            @else
                <span x-show="isSaving" x-cloak
                    class="flex items-center gap-1.5 text-amber-400 font-medium">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Menyimpan draft…
                </span>
                <span x-show="!isSaving && $wire.autosaveStatus === 'synced'" x-cloak
                    class="flex items-center gap-1.5 text-emerald-400 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Tersimpan otomatis
                </span>
                <span x-show="!isSaving && $wire.autosaveStatus === 'idle'" x-cloak
                    class="text-muted-foreground">
                    @if($orderId) Draft tersimpan @else Mulai mengisi form untuk autosave @endif
                </span>
            @endif
            @if($orderId)
                <span class="font-mono text-[10px] bg-panel-strong px-2 py-0.5 rounded text-muted-foreground">
                    {{ $orderId }}
                </span>
            @endif
        </div>

        {{-- Realtime clock WIB --}}
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-mono">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-text="currentDateTime" class="tabular-nums"></span>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         TWO-COLUMN MAIN LAYOUT
         Left:  Info Dasar + Customer + Produk + Konfigurasi
         Right: Pengiriman + Pembayaran + Desain & Produksi
         ════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 items-start">

        {{-- ─── LEFT COLUMN ──────────────────────── --}}
        <div class="space-y-3">

            {{-- SECTION 1 — Informasi Dasar --}}
            <div class="card p-4 space-y-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                    1 · Informasi Dasar
                </p>
                <div class="grid grid-cols-2 gap-3">
                    {{-- Admin --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Admin</label>
                        <input type="text" value="{{ auth()->user()->full_name ?? '—' }}" readonly
                            class="form-input text-muted-foreground cursor-default">
                    </div>
                    {{-- Order Source --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">
                            Sumber Order <span class="text-danger">*</span>
                        </label>
                        <select wire:model="order_source_id" class="form-select">
                            <option value="">— Pilih —</option>
                            @php
                                $sources = [
                                    'shopee'      => '🛒 Shopee',
                                    'tokopedia'   => '🟢 Tokopedia',
                                    'whatsapp'    => '💬 WhatsApp',
                                ];
                            @endphp
                            @foreach($sources as $val => $label)
                                <option value="{{ $val }}" {{ $order_source_id === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('order_source_id')
                            <p class="text-xs text-danger mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- SECTION 2 — Informasi Customer --}}
            <div class="card p-4 space-y-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                    2 · Informasi Customer
                </p>
                <div>
                    <label class="block text-xs font-medium text-muted-foreground mb-1">
                        Nama Akun <span class="text-danger">*</span>
                    </label>
                    <input
                        wire:model="account_name"
                        type="text"
                        placeholder="Ketik untuk mencari atau buat akun baru…"
                        autocomplete="off"
                        list="account-datalist"
                        class="form-input w-full"
                        @input="updateProductSentence()"
                    >
                    <datalist id="account-datalist">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc }}">
                        @endforeach
                    </datalist>
                    @error('account_name')
                        <p class="text-xs text-danger mt-1">{{ $message }}</p>
                    @enderror
                    @if(!$errors->has('account_name') && trim($account_name) !== '' && !$accounts->contains($account_name))
                        <p class="text-[11px] text-emerald-400 mt-1">✦ Akun baru akan dibuat otomatis</p>
                    @endif
                </div>
            </div>

            {{-- SECTION 3 — Informasi Produk --}}
            <div class="card p-4 space-y-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                    3 · Informasi Produk
                </p>
                <div class="grid grid-cols-1 gap-3">
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Product Type --}}
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">
                                Tipe Produk <span class="text-danger">*</span>
                            </label>
                            <select wire:model="product_type_id" class="form-select" @change="onTypeChange()">
                                <option value="">— Pilih Tipe —</option>
                                <template x-for="type in catalog" :key="type.id">
                                    <option :value="type.id" x-text="type.label"
                                        :selected="type.id === $wire.product_type_id"></option>
                                </template>
                            </select>
                            @error('product_type_id')
                                <p class="text-xs text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Product --}}
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-1">
                                Produk <span class="text-danger">*</span>
                            </label>
                            <select wire:model="product_id" class="form-select"
                                :disabled="!productsForType.length" @change="onProductChange()">
                                <option value="">— Pilih Produk —</option>
                                <template x-for="prod in productsForType" :key="prod.id">
                                    <option :value="prod.id" x-text="prod.label"
                                        :selected="prod.id === $wire.product_id"></option>
                                </template>
                            </select>
                            @error('product_id')
                                <p class="text-xs text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    {{-- Layout Reference --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">
                            Layout Reference <span class="text-muted-foreground font-normal">(opsional)</span>
                        </label>
                        <input wire:model="model_id" type="text"
                            placeholder="Cth: Layout A, Layout B…"
                            :disabled="!$wire.product_id"
                            class="form-input"
                            :class="!$wire.product_id ? 'opacity-50 cursor-not-allowed' : ''">
                    </div>
                </div>
            </div>

            {{-- SECTION 4 — Konfigurasi Produk (Dynamic) --}}
            <div class="card p-4 space-y-3" x-show="$wire.product_id" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                    4 · Konfigurasi Produk
                </p>
                <div class="grid grid-cols-2 gap-3">
                    {{-- Size --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Ukuran</label>
                        <input wire:model="size" type="text" placeholder="Cth: 100 x 50"
                            class="form-input" @input="updateProductSentence()">
                        <p class="text-[10px] text-muted-foreground mt-0.5">dalam cm (p × l)</p>
                    </div>
                    {{-- Text --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Teks Produk</label>
                        <input wire:model="text" type="text" placeholder="Cth: WARUNG BAROKAH"
                            class="form-input uppercase" @input="updateProductSentence()">
                        <p class="text-[10px] text-muted-foreground mt-0.5">tulisan pada produk</p>
                    </div>
                    {{-- Color --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Warna</label>
                        <input wire:model="color" type="text" placeholder="Cth: Merah, Biru Dongker"
                            class="form-input" @input="updateProductSentence()">
                    </div>
                    {{-- Variant (conditional) --}}
                    <div x-show="hasVariants" x-cloak>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Variant</label>
                        <select wire:model="variant" class="form-select" @change="updateProductSentence()">
                            <option value="">— Pilih Variant —</option>
                            <template x-for="opt in (selectedProduct?.variants ?? [])" :key="opt.id">
                                <option :value="opt.label" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                    {{-- Shape (conditional) --}}
                    <div x-show="hasShapes" x-cloak>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Bentuk (Shape)</label>
                        <select wire:model="shape" class="form-select" @change="updateProductSentence()">
                            <option value="">— Pilih Bentuk —</option>
                            <template x-for="opt in (selectedProduct?.shapes ?? [])" :key="opt.id">
                                <option :value="opt.label" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                    {{-- Bracket (conditional) --}}
                    <div x-show="hasBrackets" x-cloak>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Bracket</label>
                        <select wire:model="bracket" class="form-select" @change="updateProductSentence()">
                            <option value="">— Pilih Bracket —</option>
                            <template x-for="opt in (selectedProduct?.brackets ?? [])" :key="opt.id">
                                <option :value="opt.label" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                    {{-- Lamp (conditional) --}}
                    <div x-show="hasLamps" x-cloak>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Lampu</label>
                        <select wire:model="lamp" class="form-select" @change="updateProductSentence()">
                            <option value="">— Pilih Lampu —</option>
                            <template x-for="opt in (selectedProduct?.lamps ?? [])" :key="opt.id">
                                <option :value="opt.label" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                </div>
                {{-- Product Sentence Preview --}}
                <div x-show="$wire.product_sentence" x-cloak
                    class="mt-1 p-3 rounded-lg border border-accent/30 bg-accent/5">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-accent/70 mb-1">
                        ✦ Ringkasan Pesanan (auto-generated)
                    </p>
                    <p class="text-sm font-semibold text-foreground leading-relaxed"
                        x-text="$wire.product_sentence"></p>
                </div>
            </div>
        </div>{{-- END LEFT COLUMN --}}

        {{-- ─── RIGHT COLUMN ─────────────────────── --}}
        <div class="space-y-3">

            {{-- SECTION 5 — Pengiriman --}}
            <div class="card p-4 space-y-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                    5 · Pengiriman
                </p>
                <div class="grid grid-cols-2 gap-3">
                    {{-- Deadline --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">
                            Deadline <span class="text-danger">*</span>
                        </label>
                        <input wire:model="deadline_at" type="date"
                            min="{{ now()->addDay()->format('Y-m-d') }}" class="form-input">
                        @error('deadline_at')
                            <p class="text-xs text-danger mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Kota --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Kota Tujuan</label>
                        <input wire:model="city" type="text" placeholder="Cth: Jakarta Selatan"
                            list="city-datalist" autocomplete="off" class="form-input"
                            @input="updateAdminNotes()">
                        <datalist id="city-datalist">
                            @foreach($cities as $c)
                                <option value="{{ $c }}">
                            @endforeach
                        </datalist>
                    </div>
                    {{-- Ekspedisi --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Ekspedisi</label>
                        <select wire:model="expedition_id" class="form-select">
                            <option value="">— Pilih Ekspedisi —</option>
                            @foreach($carriers as $carrier)
                                <option value="{{ $carrier->code }}">{{ $carrier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Packing --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">
                            Packing <span class="text-danger">*</span>
                        </label>
                        <select wire:model="packing_type" class="form-select">
                            <option value="BUBBLE">Bubble</option>
                            <option value="TRIPLEK">Triplek</option>
                            <option value="KAYU">Kayu</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- SECTION 6 — Pembayaran --}}
            <div class="card p-4 space-y-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                    6 · Pembayaran
                </p>
                <div class="grid grid-cols-2 gap-3">
                    {{-- Payment Type --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">
                            Tipe Pembayaran <span class="text-danger">*</span>
                        </label>
                        <select wire:model="payment_type" class="form-select">
                            <option value="SPL">SPL</option>
                            <option value="COD">COD</option>
                            <option value="NON_COD">NON-COD</option>
                        </select>
                    </div>
                    {{-- Payment Status --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">
                            Status Bayar <span class="text-danger">*</span>
                        </label>
                        <select wire:model="payment_status" class="form-select"
                            @change="onPaymentStatusChange()">
                            <option value="UNPAID">Belum Bayar</option>
                            <option value="DP">DP</option>
                            <option value="LUNAS">Lunas</option>
                        </select>
                    </div>
                    {{-- Total Order --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">
                            Total Order <span class="text-danger">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground pointer-events-none">Rp</span>
                            <input wire:model="total_order" type="number" min="0" step="1000"
                                placeholder="0" class="form-input pl-8" @input="onTotalChange()">
                        </div>
                        @error('total_order')
                            <p class="text-xs text-danger mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Amount Paid --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Jumlah Dibayar</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground pointer-events-none">Rp</span>
                            <input wire:model="amount_paid" type="number" min="0" step="1000"
                                placeholder="0" class="form-input pl-8"
                                :readonly="$wire.payment_status !== 'DP'"
                                :class="$wire.payment_status !== 'DP'
                                    ? 'bg-panel-strong/30 cursor-default text-muted-foreground' : ''">
                        </div>
                    </div>
                </div>
                {{-- Payment hint --}}
                <p class="text-[11px]">
                    <span x-show="$wire.payment_status === 'LUNAS'" x-cloak class="text-emerald-400">
                        ✓ Jumlah dibayar = Total Order (otomatis)
                    </span>
                    <span x-show="$wire.payment_status === 'UNPAID'" x-cloak class="text-muted-foreground">
                        Jumlah dibayar = 0
                    </span>
                    <span x-show="$wire.payment_status === 'DP'" x-cloak class="text-amber-400">
                        ✎ Masukkan nominal DP secara manual
                    </span>
                </p>
            </div>

            {{-- SECTION 7 — Desain & Produksi --}}
            <div class="card p-4 space-y-3">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                    7 · Desain & Produksi
                </p>
                {{-- Design Status --}}
                <div>
                    <label class="block text-xs font-medium text-muted-foreground mb-2">Status Desain</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 cursor-pointer rounded-md border border-border
                            px-3 py-2 text-xs transition-colors hover:border-accent hover:bg-accent/5
                            has-[:checked]:border-accent has-[:checked]:bg-accent/10 has-[:checked]:text-accent">
                            <input type="radio" wire:model="design_status" value="PROCESS" class="sr-only">
                            <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                            Process — perlu dibuat
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer rounded-md border border-border
                            px-3 py-2 text-xs transition-colors hover:border-emerald-400 hover:bg-emerald-400/5
                            has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-400/10 has-[:checked]:text-emerald-400">
                            <input type="radio" wire:model="design_status" value="ACC" class="sr-only">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                            ACC — sudah disetujui
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    {{-- Reference Link --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Link Referensi</label>
                        <input wire:model="referenceLink" type="url"
                            placeholder="https://drive.google.com/… atau Canva, Pinterest…"
                            class="form-input" @input="updateAdminNotes()">
                        <p class="text-[10px] text-muted-foreground mt-0.5">Google Drive, Canva, Pinterest, dll.</p>
                    </div>
                    {{-- Production Notes --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Catatan Produksi</label>
                        <textarea wire:model="productionNotes" rows="2"
                            placeholder="Catatan teknis untuk tim produksi…"
                            class="form-input resize-none" @input="updateAdminNotes()"></textarea>
                    </div>
                    {{-- Special Request --}}
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-1">Permintaan Khusus</label>
                        <textarea wire:model="specialRequest" rows="2"
                            placeholder="Request tambahan atau keterangan khusus dari pelanggan…"
                            class="form-input resize-none" @input="updateAdminNotes()"></textarea>
                    </div>
                </div>
                {{-- Admin Notes Preview --}}
                <div x-show="$wire.admin_notes" x-cloak
                    class="p-3 rounded-lg border border-border/60 bg-panel-strong/30">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground mb-1.5">
                        📋 Catatan Internal (auto-generated)
                    </p>
                    <pre class="text-xs text-foreground font-sans whitespace-pre-wrap leading-relaxed"
                        x-text="$wire.admin_notes"></pre>
                </div>
            </div>
        </div>{{-- END RIGHT COLUMN --}}

    </div>{{-- END 2-COLUMN GRID --}}

    {{-- ════════════════════════════════════════════
         ACTION BUTTONS
         ════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between gap-3 pt-2 border-t border-border">
        <div class="flex items-center gap-2">
            @php
                $stBadge = [
                    'DRAFT'              => 'badge-draft',
                    'CONFIRMED'          => 'badge-confirmed',
                    'DESIGN_IN_PROGRESS' => 'badge-design',
                    'DESIGN_APPROVED'    => 'badge-design',
                    'IN_PRODUCTION'      => 'badge-production',
                    'ON_HOLD'            => 'badge-hold',
                ];
                $stLabel = [
                    'DRAFT'              => 'Draft',
                    'CONFIRMED'          => 'Konfirmasi',
                    'DESIGN_IN_PROGRESS' => 'Desain Proses',
                    'DESIGN_APPROVED'    => 'Desain ACC',
                    'IN_PRODUCTION'      => 'Produksi',
                    'ON_HOLD'            => 'On Hold',
                ];
            @endphp
            <span class="text-xs text-muted-foreground">Status:</span>
            <span class="badge {{ $stBadge[$status] ?? 'badge-draft' }}">
                {{ $stLabel[$status] ?? $status }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="$dispatch('closeForm')" class="btn btn-ghost">
                Batal
            </button>
            <button type="button" wire:click="save"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
                class="btn btn-primary">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Simpan Draft
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Menyimpan…
                </span>
            </button>
        </div>
    </div>

</div>

@script
<script>
Alpine.data('csOrderForm', (catalogConfig) => ({

    catalog: catalogConfig,
    isSaving: false,
    autosaveTimer: null,
    currentDateTime: '',

    get productsForType() {
        const typeId = this.$wire.product_type_id;
        if (!typeId) return [];
        const type = this.catalog.find(t => t.id === typeId);
        return type ? (type.products ?? []) : [];
    },

    get selectedProduct() {
        const pid = this.$wire.product_id;
        if (!pid) return null;
        return this.productsForType.find(p => p.id === pid) ?? null;
    },

    get hasVariants()  { return (this.selectedProduct?.variants?.length  ?? 0) > 0; },
    get hasShapes()    { return (this.selectedProduct?.shapes?.length    ?? 0) > 0; },
    get hasBrackets()  { return (this.selectedProduct?.brackets?.length  ?? 0) > 0; },
    get hasLamps()     { return (this.selectedProduct?.lamps?.length     ?? 0) > 0; },

    onTypeChange() {
        this.$wire.product_id = '';
        this.$wire.model_id   = '';
        this._clearSnapshotFields();
        this.updateProductSentence();
    },

    onProductChange() {
        this.$wire.model_id = '';
        this._clearSnapshotFields();
        this.updateProductSentence();
    },

    _clearSnapshotFields() {
        this.$wire.variant = '';
        this.$wire.shape   = '';
        this.$wire.bracket = '';
        this.$wire.lamp    = '';
    },

    onPaymentStatusChange() {
        const status = this.$wire.payment_status;
        if (status === 'LUNAS') {
            this.$wire.amount_paid = this.$wire.total_order;
        } else if (status === 'UNPAID') {
            this.$wire.amount_paid = '0';
        }
    },

    onTotalChange() {
        if (this.$wire.payment_status === 'LUNAS') {
            this.$wire.amount_paid = this.$wire.total_order;
        }
    },

    updateProductSentence() {
        const parts = [];

        const account = (this.$wire.account_name ?? '').trim();
        if (account) parts.push(account);

        const prod = this.selectedProduct;
        if (prod) parts.push(prod.label);

        const size = (this.$wire.size ?? '').trim();
        if (size) {
            const sizeClean = size.replace(/\s*[xX×]\s*/g, 'x');
            parts.push(sizeClean + ' cm');
        }

        const text = (this.$wire.text ?? '').trim();
        if (text) parts.push(text.toUpperCase());

        const combo = [
            (this.$wire.color   ?? '').trim(),
            (this.$wire.variant ?? '').trim(),
            (this.$wire.shape   ?? '').trim(),
        ].filter(Boolean);
        if (combo.length) parts.push(combo.join('/'));

        const lamp = (this.$wire.lamp ?? '').trim();
        if (lamp) parts.push(lamp);

        this.$wire.product_sentence = parts.join(' - ');
    },

    updateAdminNotes() {
        const lines = [];
        const city    = (this.$wire.city             ?? '').trim();
        const notes   = (this.$wire.productionNotes  ?? '').trim();
        const link    = (this.$wire.referenceLink    ?? '').trim();
        const special = (this.$wire.specialRequest   ?? '').trim();
        if (city)    lines.push('Kota: '     + city);
        if (notes)   lines.push('Produksi: ' + notes);
        if (link)    lines.push('Link Ref: ' + link);
        if (special) lines.push('Khusus: '   + special);
        this.$wire.admin_notes = lines.join('\n');
    },

    scheduleAutosave() {
        clearTimeout(this.autosaveTimer);
        this.autosaveTimer = setTimeout(() => this.doAutosave(), 900);
    },

    async doAutosave() {
        const hasMinData = (
            (this.$wire.account_name    ?? '').trim() &&
            (this.$wire.order_source_id ?? '') &&
            (this.$wire.product_type_id ?? '') &&
            (this.$wire.product_id      ?? '')
        );
        if (!hasMinData) return;
        this.isSaving = true;
        try {
            await this.$wire.autosave();
        } finally {
            this.isSaving = false;
        }
    },

    updateClock() {
        this.currentDateTime = new Date().toLocaleString('id-ID', {
            timeZone: 'Asia/Jakarta',
            weekday:  'short',
            day:      '2-digit',
            month:    'short',
            year:     'numeric',
            hour:     '2-digit',
            minute:   '2-digit',
            second:   '2-digit',
        }) + ' WIB';
    },

    init() {
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);
    },
}));
</script>
@endscript
