<div>
    {{-- ─── Metric cards ─────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">

        {{-- Today's orders --}}
        <div class="card p-4">
            <p class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Pesanan Hari Ini</p>
            <p class="text-3xl font-bold text-foreground">{{ $todayOrders }}</p>
        </div>

        {{-- Total orders --}}
        <div class="card p-4">
            <p class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Total Pesanan Aktif</p>
            <p class="text-3xl font-bold text-foreground">{{ $totalOrders }}</p>
        </div>

        {{-- Active production --}}
        <div class="card p-4">
            <p class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Produksi Berjalan</p>
            <p class="text-3xl font-bold text-warning">{{ $productionActive }}</p>
        </div>

        {{-- Design pending --}}
        <div class="card p-4">
            <p class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Antrian Desain</p>
            <p class="text-3xl font-bold text-accent">{{ $designPending }}</p>
        </div>

        {{-- Overdue --}}
        <div class="card p-4 {{ $overdueOrders > 0 ? 'border-danger' : '' }}">
            <p class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Lewat Deadline</p>
            <p class="text-3xl font-bold {{ $overdueOrders > 0 ? 'text-danger' : 'text-foreground' }}">
                {{ $overdueOrders }}
            </p>
        </div>

        {{-- Unread notifications --}}
        <div class="card p-4">
            <p class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Notifikasi Belum Dibaca</p>
            <p class="text-3xl font-bold {{ $unreadNotifications > 0 ? 'text-caution' : 'text-foreground' }}">
                {{ $unreadNotifications }}
            </p>
        </div>
    </div>

    {{-- ─── Order status breakdown ───────────────── --}}
    <div class="card">
        <div class="card-header">
            <h2 class="text-sm font-semibold text-foreground">Status Pesanan</h2>
            <span class="text-xs text-muted-foreground">Diperbarui setiap 15 detik</span>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                @php
                    $statusMap = [
                        'DRAFT'              => ['label' => 'Draft',        'class' => 'badge-draft'],
                        'CONFIRMED'          => ['label' => 'Konfirmasi',   'class' => 'badge-confirmed'],
                        'DESIGN_IN_PROGRESS' => ['label' => 'Desain',       'class' => 'badge-design'],
                        'DESIGN_APPROVED'    => ['label' => 'Desain ACC',   'class' => 'badge-design'],
                        'IN_PRODUCTION'      => ['label' => 'Produksi',     'class' => 'badge-production'],
                        'READY_TO_SHIP'      => ['label' => 'Siap Kirim',   'class' => 'badge-ready'],
                        'SHIPPED'            => ['label' => 'Dikirim',      'class' => 'badge-shipped'],
                        'COMPLETED'          => ['label' => 'Selesai',      'class' => 'badge-completed'],
                        'CANCELLED'          => ['label' => 'Batal',        'class' => 'badge-cancelled'],
                        'ON_HOLD'            => ['label' => 'Ditahan',      'class' => 'badge-hold'],
                    ];
                @endphp

                @foreach($statusMap as $statusKey => $info)
                <div class="text-center p-3 rounded-lg bg-panel-strong">
                    <p class="text-2xl font-bold text-foreground mb-1">
                        {{ $orderCounts[$statusKey] ?? 0 }}
                    </p>
                    <span class="badge {{ $info['class'] }}">{{ $info['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
