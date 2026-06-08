<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php
        $logs       = $this->getLogs();
        $workOrders = $this->getWorkOrders();
        $todayStats = $this->getTodayStats();
    ?>

    
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($todayStats['today_logs']); ?></div>
            <div class="text-xs text-gray-500 mt-1">Log Hari Ini</div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-950 rounded-xl border border-blue-200 dark:border-blue-800 p-4 text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo e($todayStats['started']); ?></div>
            <div class="text-xs text-blue-500 mt-1">▶ Dimulai</div>
        </div>
        <div class="bg-green-50 dark:bg-green-950 rounded-xl border border-green-200 dark:border-green-800 p-4 text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?php echo e($todayStats['completed']); ?></div>
            <div class="text-xs text-green-500 mt-1">✅ Selesai</div>
        </div>
        <div class="bg-red-50 dark:bg-red-950 rounded-xl border border-red-200 dark:border-red-800 p-4 text-center">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo e($todayStats['blocked']); ?></div>
            <div class="text-xs text-red-500 mt-1">⛔ Blocked</div>
        </div>
    </div>

    
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            
            <select wire:model.live="workOrderId"
                class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1.5 focus:ring-2 focus:ring-primary-500">
                <option value="">Semua Work Order</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $workOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($wo->id); ?>"><?php echo e($wo->order?->order_code ?? $wo->id); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </select>

            
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1.5 focus:ring-2 focus:ring-primary-500">
                <option value="">Semua Status</option>
                <option value="STARTED">STARTED</option>
                <option value="COMPLETED">COMPLETED</option>
                <option value="BLOCKED">BLOCKED</option>
                <option value="REWORK">REWORK</option>
                <option value="DONE">DONE</option>
            </select>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($workOrderId || $filterStatus): ?>
                <button wire:click="$set('workOrderId', null); $set('filterStatus', null)"
                    class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 underline">
                    Reset filter
                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span class="inline-flex h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
            <span>Live · Diperbarui pukul <?php echo e($lastRefreshed); ?></span>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logs->isEmpty()): ?>
        <div class="p-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="text-5xl mb-3">📋</div>
            <p class="text-gray-400 text-sm">Belum ada log progres untuk filter yang dipilih.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Kode Pesanan</th>
                        <th class="px-4 py-3">Tahap</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Catatan</th>
                        <th class="px-4 py-3">Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $statusColors = [
                                'STARTED'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'COMPLETED' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'DONE'      => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'BLOCKED'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'REWORK'    => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                            ];
                            $statusVal = $log->status?->value ?? (string)$log->status;
                        ?>
                        <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">
                                <div><?php echo e($log->created_at?->format('d/m/Y')); ?></div>
                                <div class="font-medium text-gray-700 dark:text-gray-300"><?php echo e($log->created_at?->format('H:i:s')); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono font-semibold text-primary-600 dark:text-primary-400">
                                    <?php echo e($log->workOrder?->order?->order_code ?? '-'); ?>

                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                <?php echo e($log->stage?->name ?? '-'); ?>

                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($statusColors[$statusVal] ?? 'bg-gray-100 text-gray-600'); ?>">
                                    <?php echo e($statusVal ?: '-'); ?>

                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 max-w-xs truncate" title="<?php echo e($log->note); ?>">
                                <?php echo e($log->note ? Str::limit($log->note, 50) : '-'); ?>

                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                <?php echo e($log->personnel?->name ?? '-'); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-2 text-right">
            Menampilkan <?php echo e($logs->count()); ?> log terbaru · Auto-refresh setiap 10 detik
        </p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH D:\Project\erplrv\resources\views/filament/pages/progress-board.blade.php ENDPATH**/ ?>