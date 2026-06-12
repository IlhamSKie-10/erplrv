@php
    $record = $getRecord();
    
    // Attempt to get product type name
    $productTypeName = '';
    if ($record->order && $record->order->productType) {
        $productTypeName = $record->order->productType->name;
    } elseif ($record->order && isset($record->order->form_snapshot['product_type_name'])) {
        $productTypeName = $record->order->form_snapshot['product_type_name'];
    }
    
    // Determine stages sequence
    if (in_array($productTypeName, ['Advertising 1', 'Advertising 2'])) {
        $stages = ['LAS', 'LASER', 'RANGKAI', 'STRC UV', 'CD', 'FINISHING', 'BUBBLE', 'KIRIM'];
    } elseif (in_array($productTypeName, ['Home Decor', 'Homedecor', 'Logo & Tulisan Ukir', 'Logo & Ukir'])) {
        $stages = ['CNC', 'RANGKAI', 'CAT', 'FINISHING', 'BUBBLE', 'KIRIM'];
    } else {
        // Fallback: just show current stage if unknown or generic progress
        $stages = $record->currentStage ? [$record->currentStage->name] : ['PRODUKSI'];
    }
    
    // Determine statuses
    $completedStages = $record->progressLogs->whereIn('status', ['COMPLETED', 'DONE'])->pluck('stage.name')->toArray();
    $currentStageName = $record->currentStage?->name;
    $isDone = in_array($record->status?->value ?? $record->status, ['COMPLETED', 'DONE']);
@endphp

<div class="flex flex-wrap items-center gap-y-1 gap-x-0.5 min-w-[250px] max-w-[400px]">
    @foreach($stages as $index => $stage)
        @php
            // A stage is considered completed if it's in the log with COMPLETED status
            // Or if the whole WO is DONE, assume all stages up to the last one are done.
            $isCompleted = in_array($stage, $completedStages);
            
            // It is current if it matches currentStage, and WO is not done
            $isCurrent = ($stage === $currentStageName && !$isDone);
            
            if ($isDone) {
                $isCompleted = true; // Mark all as completed visually if the whole WO is marked DONE
                $isCurrent = false;
            }
            
            $bgColor = 'bg-gray-200 dark:bg-gray-700';
            $textColor = 'text-gray-500 dark:text-gray-400';
            
            if ($isCompleted) {
                $bgColor = 'bg-success-500';
                $textColor = 'text-white';
            } elseif ($isCurrent) {
                $bgColor = 'bg-primary-500 animate-pulse';
                $textColor = 'text-white';
            }
        @endphp
        
        <div class="flex items-center">
            <span class="px-1.5 py-0.5 text-[9px] uppercase tracking-wider font-bold rounded-sm whitespace-nowrap {{ $bgColor }} {{ $textColor }}">
                {{ $stage }}
            </span>
            
            @if(!$loop->last)
                <svg class="w-3 h-3 text-gray-300 dark:text-gray-600 mx-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
            @endif
        </div>
    @endforeach
</div>
