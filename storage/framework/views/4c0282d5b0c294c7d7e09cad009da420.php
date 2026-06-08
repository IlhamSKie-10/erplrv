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
        $jobs  = $this->getJobs();
        $stats = $this->getStats();
        $queues = $this->getQueues();
    ?>

    
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($stats['total']); ?></div>
            <div class="text-xs text-gray-500 mt-1">Total Aktif</div>
        </div>
        <div class="bg-red-50 dark:bg-red-950 rounded-xl border border-red-200 dark:border-red-800 p-4 text-center">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo e($stats['overdue']); ?></div>
            <div class="text-xs text-red-500 mt-1">🔴 Terlambat</div>
        </div>
        <div class="bg-orange-50 dark:bg-orange-950 rounded-xl border border-orange-200 dark:border-orange-800 p-4 text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400"><?php echo e($stats['due_today']); ?></div>
            <div class="text-xs text-orange-500 mt-1">🟠 Hari Ini</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-950 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo e($stats['h3']); ?></div>
            <div class="text-xs text-yellow-500 mt-1">🟡 H-3</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-300"><?php echo e($stats['blocked']); ?></div>
            <div class="text-xs text-gray-500 mt-1">⛔ Blocked</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-300"><?php echo e($stats['held']); ?></div>
            <div class="text-xs text-gray-500 mt-1">⏸ On Hold</div>
        </div>
    </div>

    
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            
            <button wire:click="$set('activeQueue', null)"
                class="px-3 py-1.5 text-xs rounded-lg font-medium transition-colors
                    <?php echo e(!$activeQueue ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'); ?>">
                Semua Antrian
            </button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $queues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $queue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button wire:click="$set('activeQueue', '<?php echo e($queue->id); ?>')"
                    class="px-3 py-1.5 text-xs rounded-lg font-medium transition-colors
                        <?php echo e($activeQueue === (string)$queue->id ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'); ?>">
                    <?php echo e($queue->name); ?>

                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span class="inline-flex h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
            <span>Live · Diperbarui pukul <?php echo e($lastRefreshed); ?></span>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($jobs->isEmpty()): ?>
        <div class="p-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="text-5xl mb-3">✅</div>
            <p class="text-gray-400 text-sm">Tidak ada work order aktif. Semua selesai!</p>
        </div>
    <?php else: ?>
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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $jobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="<?php echo e($job->deadlineBandRowClass()); ?> hover:brightness-95 dark:hover:brightness-110 transition-all
                            <?php if($job->is_held): ?> opacity-60 <?php endif; ?>
                            <?php if($job->is_pinned): ?> ring-2 ring-inset ring-yellow-400 <?php endif; ?>">

                            
                            <td class="px-4 py-3 font-bold text-gray-400 text-center"><?php echo e($index + 1); ?></td>

                            
                            <td class="px-4 py-3">
                                <span class="font-mono font-semibold text-primary-600 dark:text-primary-400">
                                    <?php echo e($job->order?->order_code ?? '-'); ?>

                                </span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($job->is_pinned): ?>
                                    <span title="Dipinned" class="ml-1">📌</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($job->is_held): ?>
                                    <span title="On Hold" class="ml-1">⏸</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                <?php echo e($job->order?->account?->name ?? '-'); ?>

                            </td>

                            
                            <td class="px-4 py-3 max-w-xs truncate text-gray-600 dark:text-gray-400" 
                                title="<?php echo e($job->order?->product_sentence); ?>">
                                <?php echo e(Str::limit($job->order?->product_sentence ?? '-', 40)); ?>

                            </td>

                            
                            <td class="px-4 py-3">
                                <?php
                                    $statusColors = [
                                        'STARTED'     => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'BLOCKED'     => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'COMPLETED'   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'REWORK'      => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'NOT_STARTED' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    ];
                                    $statusVal = $job->status?->value ?? 'NOT_STARTED';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($statusColors[$statusVal] ?? 'bg-gray-100 text-gray-600'); ?>">
                                    <?php echo e($statusVal); ?>

                                </span>
                            </td>

                            
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                <?php echo e($job->currentStage?->name ?? '-'); ?>

                            </td>

                            
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                <?php echo e($job->assignedPersonnel?->name ?? '-'); ?>

                            </td>

                            
                            <td class="px-4 py-3">
                                <?php
                                    $bandColors = [
                                        'OVERDUE'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'DUE_TODAY' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'H3'        => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'SAFE'      => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'DONE'      => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    ];
                                    $bandVal = $job->deadline_band?->value ?? 'SAFE';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($bandColors[$bandVal] ?? 'bg-gray-100 text-gray-600'); ?>">
                                    <?php echo e($job->deadlineBandLabel()); ?>

                                </span>
                            </td>

                            
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 text-right">
                                <?php echo e(number_format($job->dynamic_score ?? 0, 1)); ?>

                            </td>

                            
                            <td class="px-4 py-3 text-sm font-medium
                                <?php if(($job->deadline_band?->value ?? '') === 'OVERDUE'): ?> text-red-600 dark:text-red-400
                                <?php elseif(($job->deadline_band?->value ?? '') === 'DUE_TODAY'): ?> text-orange-600 dark:text-orange-400
                                <?php else: ?> text-gray-500 dark:text-gray-400
                                <?php endif; ?>">
                                <?php echo e($job->order?->deadline_at?->format('d/m/Y') ?? '-'); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-2 text-right">
            Menampilkan <?php echo e($jobs->count()); ?> work order aktif · Auto-refresh setiap 10 detik
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
<?php /**PATH D:\Project\erplrv\resources\views/filament/pages/priority-board.blade.php ENDPATH**/ ?>