<div x-data="{ confirmDelete: null }">

    {{-- ─── Header bar ──────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Search --}}
            <input
                wire:model.live.debounce.400ms="search"
                type="text"
                placeholder="Cari kode / nama pelanggan…"
                class="form-input w-64"
            >

            {{-- Status filter --}}
            <select wire:model.live="statusFilter" class="form-select w-auto">
                <option value="">Semua Status</option>
                <option value="DRAFT">Draft</option>
                <option value="CONFIRMED">Konfirmasi</option>
                <option value="DESIGN_IN_PROGRESS">Desain Proses</option>
                <option value="DESIGN_APPROVED">Desain ACC</option>
                <option value="IN_PRODUCTION">Produksi</option>
                <option value="READY_TO_SHIP">Siap Kirim</option>
                <option value="SHIPPED">Dikirim</option>
                <option value="COMPLETED">Selesai</option>
                <option value="CANCELLED">Batal</option>
                <option value="ON_HOLD">Ditahan</option>
            </select>

            {{-- Payment filter --}}
            <select wire:model.live="paymentFilter" class="form-select w-auto">
                <option value="">Semua Pembayaran</option>
                <option value="UNPAID">Belum Bayar</option>
                <option value="DP">DP</option>
                <option value="LUNAS">Lunas</option>
            </select>
        </div>

        <button wire:click="openCreate" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Pesanan Baru
        </button>
    </div>

    {{-- ─── Table ───────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Desain</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php 
                        $isOverdue = $order->deadline_at && $order->deadline_at < now()
                            && !in_array($order->status?->value, ['COMPLETED','SHIPPED','CANCELLED']);
                    @endphp
                    <tr class="{{ $isOverdue ? 'bg-danger-soft/40' : '' }}">
                        <td class="font-mono text-xs font-medium">
                            {{ $order->order_code }}
                            @if($isOverdue)
                                <span class="badge badge-cancelled ml-1">Terlambat</span>
                            @endif
                        </td>
                        <td>
                            <div class="font-medium text-sm">{{ $order->account?->name ?? '—' }}</div>
                            <div class="text-xs text-muted-foreground">{{ $order->orderSource?->code ?? '' }}</div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $order->productType?->name ?? '—' }}</div>
                            <div class="text-xs text-muted-foreground">{{ $order->product?->name ?? '' }}</div>
                        </td>
                        <td class="text-sm whitespace-nowrap">
                            {{ $order->deadline_at?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td>
                            @php
                                $statusClasses = [
                                    'DRAFT'              => 'badge-draft',
                                    'CONFIRMED'          => 'badge-confirmed',
                                    'DESIGN_IN_PROGRESS' => 'badge-design',
                                    'DESIGN_APPROVED'    => 'badge-design',
                                    'IN_PRODUCTION'      => 'badge-production',
                                    'READY_TO_SHIP'      => 'badge-ready',
                                    'SHIPPED'            => 'badge-shipped',
                                    'COMPLETED'          => 'badge-completed',
                                    'CANCELLED'          => 'badge-cancelled',
                                    'ON_HOLD'            => 'badge-hold',
                                ];
                                $statusLabels = [
                                    'DRAFT'              => 'Draft',
                                    'CONFIRMED'          => 'Konfirmasi',
                                    'DESIGN_IN_PROGRESS' => 'Desain Proses',
                                    'DESIGN_APPROVED'    => 'Desain ACC',
                                    'IN_PRODUCTION'      => 'Produksi',
                                    'READY_TO_SHIP'      => 'Siap Kirim',
                                    'SHIPPED'            => 'Dikirim',
                                    'COMPLETED'          => 'Selesai',
                                    'CANCELLED'          => 'Batal',
                                    'ON_HOLD'            => 'Ditahan',
                                ];
                                $sv = $order->status?->value ?? '';
                            @endphp
                            <span class="badge {{ $statusClasses[$sv] ?? 'badge-draft' }}">
                                {{ $statusLabels[$sv] ?? $sv }}
                            </span>
                        </td>
                        <td>
                            @php
                                $pv = $order->payment_status?->value ?? '';
                                $pClasses = ['UNPAID' => 'badge-unpaid','DP' => 'badge-dp','LUNAS' => 'badge-lunas'];
                                $pLabels  = ['UNPAID' => 'Belum Bayar','DP' => 'DP','LUNAS' => 'Lunas'];
                            @endphp
                            <span class="badge {{ $pClasses[$pv] ?? '' }}">{{ $pLabels[$pv] ?? $pv }}</span>
                        </td>
                        <td class="text-sm whitespace-nowrap">
                            Rp {{ number_format($order->total_order, 0, ',', '.') }}
                        </td>
                        <td>
                            @php $ds = $order->design_status?->value ?? '' @endphp
                            <span class="badge {{ $ds === 'ACC' ? 'badge-completed' : 'badge-draft' }}">
                                {{ $ds === 'ACC' ? 'ACC' : 'Proses' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                {{-- Edit --}}
                                <button wire:click="openEdit('{{ $order->id }}')"
                                    class="btn btn-ghost btn-sm" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>

                                {{-- Duplicate --}}
                                <button wire:click="duplicate('{{ $order->id }}')"
                                    wire:confirm="Duplikat pesanan {{ $order->order_code }}? Draft baru akan dibuat."
                                    class="btn btn-ghost btn-sm text-muted-foreground" title="Duplikat">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>

                                {{-- Submit (only DRAFT/CONFIRMED) --}}
                                @if(in_array($order->status?->value, ['DRAFT','CONFIRMED']))
                                <button wire:click="submit('{{ $order->id }}')"
                                    wire:confirm="Submit pesanan {{ $order->order_code }} ke desainer?"
                                    class="btn btn-ghost btn-sm text-accent" title="Kirim ke Desainer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                @endif

                                {{-- Delete (only DRAFT) --}}
                                @if($order->status?->value === 'DRAFT')
                                <button wire:click="delete('{{ $order->id }}')"
                                    wire:confirm="Hapus pesanan {{ $order->order_code }}? Tindakan ini tidak bisa dibatalkan."
                                    class="btn btn-ghost btn-sm text-danger" title="Hapus">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-12 text-muted-foreground">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Tidak ada pesanan ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
        <div class="px-4 py-3 border-t border-border">
            {{ $orders->links() }}
        </div>
        @endif
    </div>

    {{-- ─── Order Form Modal ────────────────────── --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-start justify-center pt-16 px-4"
         x-data x-init="$el.scrollTop = 0">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40" wire:click="closeForm"></div>

        {{-- Modal --}}
        <div class="relative w-full max-w-[98%] bg-panel rounded-xl shadow-2xl overflow-hidden h-[95vh] flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-border shrink-0">
                <h2 class="text-base font-semibold text-foreground">
                    {{ $editingOrderId ? 'Edit Pesanan' : 'Pesanan Baru' }}
                </h2>
                <button wire:click="closeForm" class="text-muted-foreground hover:text-foreground">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 p-5">
                @livewire('c-s.order-form', ['orderId' => $editingOrderId], key($editingOrderId ?? 'new'))
            </div>
        </div>
    </div>
    @endif
</div>
