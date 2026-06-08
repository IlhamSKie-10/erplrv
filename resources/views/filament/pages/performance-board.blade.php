<x-filament-panels::page>
    @php
        $orderFunnel      = $orderFunnel ?? [];
        $deadlineStats    = $deadlineStats ?? [];
        $paymentStats     = $paymentStats ?? [];
        $throughput       = $throughput ?? [];
        $personnelKpi     = $personnelKpi ?? collect();
        $bottleneckStages = $bottleneckStages ?? collect();
        $maxThroughput    = max(array_column($throughput, 'count') ?: [1]);
    @endphp

    {{-- ===================== SECTION 1: Deadline & Status Overview ===================== --}}
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Status Work Order Aktif
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $deadlineStats['total'] ?? 0 }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Aktif</div>
            </div>
            <div class="bg-red-50 dark:bg-red-950 rounded-xl border border-red-200 dark:border-red-800 p-4 text-center">
                <div class="text-3xl font-bold text-red-600">{{ $deadlineStats['overdue'] ?? 0 }}</div>
                <div class="text-xs text-red-500 mt-1">🔴 Terlambat</div>
            </div>
            <div class="bg-orange-50 dark:bg-orange-950 rounded-xl border border-orange-200 dark:border-orange-800 p-4 text-center">
                <div class="text-3xl font-bold text-orange-600">{{ $deadlineStats['due_today'] ?? 0 }}</div>
                <div class="text-xs text-orange-500 mt-1">🟠 Hari Ini</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-950 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4 text-center">
                <div class="text-3xl font-bold text-yellow-600">{{ $deadlineStats['h3'] ?? 0 }}</div>
                <div class="text-xs text-yellow-500 mt-1">🟡 H-3</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-gray-700 dark:text-gray-300">{{ $deadlineStats['blocked'] ?? 0 }}</div>
                <div class="text-xs text-gray-500 mt-1">⛔ Blocked</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-950 rounded-xl border border-purple-200 dark:border-purple-800 p-4 text-center">
                <div class="text-3xl font-bold text-purple-600">{{ $deadlineStats['rework'] ?? 0 }}</div>
                <div class="text-xs text-purple-500 mt-1">🔄 Rework</div>
            </div>
        </div>
    </div>

    {{-- ===================== SECTION 2: Order Funnel ===================== --}}
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Funnel Pesanan
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            @php $totalOrders = array_sum(array_column($orderFunnel, 'count')) ?: 1; @endphp
            <div class="space-y-2">
                @foreach($orderFunnel as $stage)
                    <div class="flex items-center gap-3">
                        <div class="w-28 text-xs text-gray-600 dark:text-gray-400 text-right flex-shrink-0">
                            {{ $stage['label'] }}
                        </div>
                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                            @php $pct = $totalOrders > 0 ? ($stage['count'] / $totalOrders * 100) : 0; @endphp
                            <div class="{{ $stage['color'] }} dark:opacity-80 h-6 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                style="width: {{ max($pct, $stage['count'] > 0 ? 4 : 0) }}%">
                                @if($stage['count'] > 0)
                                    <span class="text-xs font-bold text-gray-700">{{ $stage['count'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="w-8 text-xs font-semibold text-gray-700 dark:text-gray-300 flex-shrink-0">
                            {{ $stage['count'] > 0 ? $stage['count'] : '' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===================== SECTION 3: Throughput Chart ===================== --}}
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Throughput Produksi ({{ count($throughput) }} Hari Terakhir)
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-end gap-2 h-32">
                @foreach($throughput as $day)
                    @php $height = $maxThroughput > 0 ? ($day['count'] / $maxThroughput * 100) : 0; @endphp
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ $day['count'] > 0 ? $day['count'] : '' }}
                        </span>
                        <div class="w-full rounded-t-md transition-all duration-500 {{ $day['count'] > 0 ? 'bg-primary-500' : 'bg-gray-100 dark:bg-gray-700' }}"
                            style="height: {{ max($height, $day['count'] > 0 ? 8 : 4) }}%">
                        </div>
                        <span class="text-xs text-gray-400">{{ $day['date'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-2 text-center">
                Jumlah log "COMPLETED" per hari
            </p>
        </div>
    </div>

    {{-- ===================== SECTION 4: Payment & Personnnel ===================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Payment Breakdown --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Ringkasan Pembayaran
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500">Total Revenue</span>
                    <span class="font-bold text-gray-900 dark:text-white">
                        Rp {{ number_format($paymentStats['total_revenue'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500">Sudah Terkumpul</span>
                    <span class="font-bold text-green-600">
                        Rp {{ number_format($paymentStats['total_collected'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-3 mt-2">
                    <div class="text-center p-3 bg-red-50 dark:bg-red-950 rounded-lg">
                        <div class="text-xl font-bold text-red-600">{{ $paymentStats['unpaid']['count'] ?? 0 }}</div>
                        <div class="text-xs text-red-500">Belum Bayar</div>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-950 rounded-lg">
                        <div class="text-xl font-bold text-yellow-600">{{ $paymentStats['dp']['count'] ?? 0 }}</div>
                        <div class="text-xs text-yellow-500">DP</div>
                    </div>
                    <div class="text-center p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                        <div class="text-xl font-bold text-green-600">{{ $paymentStats['lunas']['count'] ?? 0 }}</div>
                        <div class="text-xs text-green-500">Lunas</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottleneck Stages --}}
        <div>
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Stasiun Paling Sering Blocked (30 Hari)
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                @if($bottleneckStages->isEmpty())
                    <div class="p-6 text-center text-gray-400 text-sm">
                        ✅ Tidak ada bottleneck tercatat!
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Stasiun</th>
                                <th class="px-4 py-3 text-right">Blocked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($bottleneckStages as $i => $stage)
                                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 flex items-center gap-2">
                                        <span class="text-red-400 font-bold text-xs">#{{ $i+1 }}</span>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $stage->stage?->name ?? 'Tidak diketahui' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                            {{ $stage->blocked_count }}x
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== SECTION 5: Personnel Leaderboard ===================== --}}
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Leaderboard Petugas Produksi (30 Hari Terakhir)
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if($personnelKpi->isEmpty())
                <div class="p-6 text-center text-gray-400 text-sm">
                    Belum ada data progres dalam 30 hari terakhir.
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left w-8">#</th>
                            <th class="px-4 py-3 text-left">Nama Petugas</th>
                            <th class="px-4 py-3 text-center">Total Log</th>
                            <th class="px-4 py-3 text-center">✅ Selesai</th>
                            <th class="px-4 py-3 text-center">⛔ Blocked</th>
                            <th class="px-4 py-3 text-left">Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($personnelKpi as $i => $kpi)
                            @php
                                $maxLog = $personnelKpi->max('log_count') ?: 1;
                                $pct = $kpi->log_count / $maxLog * 100;
                            @endphp
                            <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-4 py-3 font-bold text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">
                                    {{ $kpi->personnel?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">
                                    {{ $kpi->log_count }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-green-600 font-medium">{{ $kpi->completed_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="{{ $kpi->blocked_count > 0 ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                                        {{ $kpi->blocked_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                        <div class="bg-primary-500 h-2 rounded-full transition-all duration-500"
                                            style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <p class="text-xs text-gray-400 text-right">
        Auto-refresh setiap 30 detik · Data diambil secara realtime dari database
    </p>
</x-filament-panels::page>
