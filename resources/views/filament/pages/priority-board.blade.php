<x-filament-panels::page>
    @php
        $jobs  = $this->getJobs();
        $stats = $this->getStats();
        $queues = $this->getQueues();
    @endphp

    {{-- Header Stats Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Aktif</div>
        </div>
        <div class="bg-red-50 dark:bg-red-950 rounded-xl border border-red-200 dark:border-red-800 p-4 text-center">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['overdue'] }}</div>
            <div class="text-xs text-red-500 mt-1">🔴 Terlambat</div>
        </div>
        <div class="bg-orange-50 dark:bg-orange-950 rounded-xl border border-orange-200 dark:border-orange-800 p-4 text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['due_today'] }}</div>
            <div class="text-xs text-orange-500 mt-1">🟠 Hari Ini</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-950 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['h3'] }}</div>
            <div class="text-xs text-yellow-500 mt-1">🟡 H-3</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $stats['blocked'] }}</div>
            <div class="text-xs text-gray-500 mt-1">⛔ Blocked</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $stats['held'] }}</div>
            <div class="text-xs text-gray-500 mt-1">⏸ On Hold</div>
        </div>
    </div>

    {{-- Toolbar: Queue Filter + Live Badge --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            {{-- Queue filter buttons --}}
            <button wire:click="$set('activeQueue', null)"
                class="px-3 py-1.5 text-xs rounded-lg font-medium transition-colors
                    {{ !$activeQueue ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                Semua Antrian
            </button>
            @foreach($queues as $queue)
                <button wire:click="$set('activeQueue', '{{ $queue->id }}')"
                    class="px-3 py-1.5 text-xs rounded-lg font-medium transition-colors
                        {{ $activeQueue === (string)$queue->id ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $queue->name }}
                </button>
            @endforeach
        </div>

        {{-- Live Indicator --}}
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span class="inline-flex h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
            <span>Live · Diperbarui pukul {{ $lastRefreshed }}</span>
        </div>
    </div>

    {{-- Jobs Table --}}
    @if($jobs->isEmpty())
        <div class="p-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="text-5xl mb-3">✅</div>
            <p class="text-gray-400 text-sm">Tidak ada work order aktif. Semua selesai!</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3 w-8">#</th>
                        <th class="px-4 py-3">Kode Pesanan</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Tahap</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3">Urgensi</th>
                        <th class="px-4 py-3 text-right">Skor</th>
                        <th class="px-4 py-3">Deadline</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($jobs as $index => $job)
                        <tr class="{{ $job->deadlineBandRowClass() }} hover:brightness-95 dark:hover:brightness-110 transition-all
                            @if($job->is_held) opacity-60 @endif
                            @if($job->is_pinned) ring-2 ring-inset ring-yellow-400 @endif">

                            {{-- Rank --}}
                            <td class="px-4 py-3 font-bold text-gray-400 text-center">{{ $index + 1 }}</td>

                            {{-- Order Code + flags --}}
                            <td class="px-4 py-3">
                                <span class="font-mono font-semibold text-primary-600 dark:text-primary-400">
                                    {{ $job->order?->order_code ?? '-' }}
                                </span>
                                @if($job->is_pinned)
                                    <span title="Dipinned" class="ml-1">📌</span>
                                @endif
                                @if($job->is_held)
                                    <span title="On Hold" class="ml-1">⏸</span>
                                @endif
                            </td>

                            {{-- Customer --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $job->order?->account?->name ?? '-' }}
                            </td>

                            {{-- Product --}}
                            <td class="px-4 py-3 max-w-xs truncate text-gray-600 dark:text-gray-400" 
                                title="{{ $job->order?->product_sentence }}">
                                {{ Str::limit($job->order?->product_sentence ?? '-', 40) }}
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'STARTED'     => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'BLOCKED'     => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'COMPLETED'   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'REWORK'      => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'NOT_STARTED' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    ];
                                    $statusVal = $job->status?->value ?? 'NOT_STARTED';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$statusVal] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statusVal }}
                                </span>
                            </td>

                            {{-- Stage --}}
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $job->currentStage?->name ?? '-' }}
                            </td>

                            {{-- Personnel --}}
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $job->assignedPersonnel?->name ?? '-' }}
                            </td>

                            {{-- Deadline Band --}}
                            <td class="px-4 py-3">
                                @php
                                    $bandColors = [
                                        'OVERDUE'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'DUE_TODAY' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'H3'        => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'SAFE'      => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'DONE'      => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    ];
                                    $bandVal = $job->deadline_band?->value ?? 'SAFE';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bandColors[$bandVal] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $job->deadlineBandLabel() }}
                                </span>
                            </td>

                            {{-- Score --}}
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 text-right">
                                {{ number_format($job->dynamic_score ?? 0, 1) }}
                            </td>

                            {{-- Deadline Date --}}
                            <td class="px-4 py-3 text-sm font-medium
                                @if(($job->deadline_band?->value ?? '') === 'OVERDUE') text-red-600 dark:text-red-400
                                @elseif(($job->deadline_band?->value ?? '') === 'DUE_TODAY') text-orange-600 dark:text-orange-400
                                @else text-gray-500 dark:text-gray-400
                                @endif">
                                {{ $job->order?->deadline_at?->format('d/m/Y') ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-2 text-right">
            Menampilkan {{ $jobs->count() }} work order aktif · Auto-refresh setiap 10 detik
        </p>
    @endif
</x-filament-panels::page>
