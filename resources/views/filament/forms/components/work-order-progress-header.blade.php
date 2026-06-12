@php
    $order = $record?->order;
    
    // Data for the table
    $orderCode = $order?->order_code ?? '-';
    $desc = $order?->product_sentence ?? '-';
    $deadline = $order?->deadline_at ? $order->deadline_at->format('d/m/Y') : '-';
    
    // Attempt to get product type name
    $productTypeName = '';
    if ($order && $order->productType) {
        $productTypeName = $order->productType->name;
    } elseif ($order && isset($order->form_snapshot['product_type_name'])) {
        $productTypeName = $order->form_snapshot['product_type_name'];
    }
    
    // Determine stages sequence
    if (in_array($productTypeName, ['Advertising 1', 'Advertising 2'])) {
        $stages = ['LAS', 'LASER', 'RANGKAI', 'STRC UV', 'CD', 'FINISHING', 'BUBBLE', 'KIRIM'];
    } elseif (in_array($productTypeName, ['Home Decor', 'Homedecor', 'Logo & Tulisan Ukir', 'Logo & Ukir'])) {
        $stages = ['CNC', 'RANGKAI', 'CAT', 'FINISHING', 'BUBBLE', 'KIRIM'];
    } else {
        $stages = $record->currentStage ? [$record->currentStage->name] : ['PRODUKSI'];
    }
@endphp

<div wire:poll="5s" class="mb-6 fi-ta-content divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 ring-1 ring-gray-950/5 rounded-xl bg-white shadow-sm dark:bg-gray-900">
    <table class="fi-ta-table w-full text-left divide-y divide-gray-200 dark:divide-white/5">
        <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
                <th rowspan="2" class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white border-r border-gray-200 dark:border-white/10 align-middle text-center">
                    Kode Pesanan
                </th>
                <th rowspan="2" class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white border-r border-gray-200 dark:border-white/10 align-middle text-center min-w-[200px]">
                    Deskripsi Produk
                </th>
                <th rowspan="2" class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white border-r border-gray-200 dark:border-white/10 align-middle text-center">
                    Deadline
                </th>
                <th colspan="{{ count($stages) }}" class="fi-ta-header-cell px-3 py-2 text-sm font-semibold text-gray-950 dark:text-white text-center border-b border-gray-200 dark:border-white/10 uppercase tracking-widest">
                    PROGRESS
                </th>
            </tr>
            <tr>
                @foreach($stages as $stage)
                    <th class="fi-ta-header-cell px-3 py-2 text-xs font-semibold text-gray-950 dark:text-white border-r border-gray-200 dark:border-white/10 last:border-r-0 text-center">
                        {{ $stage }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                <td class="fi-ta-cell px-3 py-4 text-sm font-medium text-gray-950 dark:text-white border-r border-gray-200 dark:border-white/10 align-middle text-center">
                    {{ $orderCode }}
                </td>
                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-white/10 align-middle">
                    {{ $desc }}
                </td>
                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-white/10 align-middle text-center">
                    {{ $deadline }}
                </td>
                @foreach($stages as $stage)
                    <td class="fi-ta-cell px-2 py-3 text-sm text-gray-950 dark:text-white border-r border-gray-200 dark:border-white/10 last:border-r-0 text-center align-top min-w-[80px]">
                        @php
                            $stageLogs = $record->progressLogs->where('stage.name', $stage);
                            $completedLog = $stageLogs->whereIn('status', ['COMPLETED', 'DONE'])->first();
                            $startedLog = $stageLogs->where('status', 'STARTED')->first();
                        @endphp
                        
                        @if($completedLog)
                            <div class="text-[10px] text-success-600 dark:text-success-400 font-bold mb-1">✓ SELESAI</div>
                            <div class="text-[11px] font-medium leading-tight">{{ $completedLog->personnel?->full_name ?? '-' }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $completedLog->created_at->format('d/m H:i') }}</div>
                        @elseif($startedLog)
                            <div class="text-[10px] text-primary-600 dark:text-primary-400 font-bold mb-1 animate-pulse">PROSES</div>
                            <div class="text-[11px] font-medium leading-tight">{{ $startedLog->personnel?->full_name ?? '-' }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $startedLog->created_at->format('d/m H:i') }}</div>
                        @else
                            <span class="text-gray-300 dark:text-gray-600">-</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
