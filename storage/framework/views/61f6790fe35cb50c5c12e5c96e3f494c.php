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
        $orderFunnel      = $orderFunnel ?? [];
        $deadlineStats    = $deadlineStats ?? [];
        $paymentStats     = $paymentStats ?? [];
        $throughput       = $throughput ?? [];
        $personnelKpi     = $personnelKpi ?? collect();
        $bottleneckStages = $bottleneckStages ?? collect();
        $maxThroughput    = max(array_column($throughput, 'count') ?: [1]);
    ?>

    
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Status Work Order Aktif
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo e($deadlineStats['total'] ?? 0); ?></div>
                <div class="text-xs text-gray-500 mt-1">Total Aktif</div>
            </div>
            <div class="bg-red-50 dark:bg-red-950 rounded-xl border border-red-200 dark:border-red-800 p-4 text-center">
                <div class="text-3xl font-bold text-red-600"><?php echo e($deadlineStats['overdue'] ?? 0); ?></div>
                <div class="text-xs text-red-500 mt-1">🔴 Terlambat</div>
            </div>
            <div class="bg-orange-50 dark:bg-orange-950 rounded-xl border border-orange-200 dark:border-orange-800 p-4 text-center">
                <div class="text-3xl font-bold text-orange-600"><?php echo e($deadlineStats['due_today'] ?? 0); ?></div>
                <div class="text-xs text-orange-500 mt-1">🟠 Hari Ini</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-950 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4 text-center">
                <div class="text-3xl font-bold text-yellow-600"><?php echo e($deadlineStats['h3'] ?? 0); ?></div>
                <div class="text-xs text-yellow-500 mt-1">🟡 H-3</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-gray-700 dark:text-gray-300"><?php echo e($deadlineStats['blocked'] ?? 0); ?></div>
                <div class="text-xs text-gray-500 mt-1">⛔ Blocked</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-950 rounded-xl border border-purple-200 dark:border-purple-800 p-4 text-center">
                <div class="text-3xl font-bold text-purple-600"><?php echo e($deadlineStats['rework'] ?? 0); ?></div>
                <div class="text-xs text-purple-500 mt-1">🔄 Rework</div>
            </div>
        </div>
    </div>

    
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Funnel Pesanan
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <?php $totalOrders = array_sum(array_column($orderFunnel, 'count')) ?: 1; ?>
            <div class="space-y-2">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $orderFunnel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center gap-3">
                        <div class="w-28 text-xs text-gray-600 dark:text-gray-400 text-right flex-shrink-0">
                            <?php echo e($stage['label']); ?>

                        </div>
                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                            <?php $pct = $totalOrders > 0 ? ($stage['count'] / $totalOrders * 100) : 0; ?>
                            <div class="<?php echo e($stage['color']); ?> dark:opacity-80 h-6 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                style="width: <?php echo e(max($pct, $stage['count'] > 0 ? 4 : 0)); ?>%">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stage['count'] > 0): ?>
                                    <span class="text-xs font-bold text-gray-700"><?php echo e($stage['count']); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <div class="w-8 text-xs font-semibold text-gray-700 dark:text-gray-300 flex-shrink-0">
                            <?php echo e($stage['count'] > 0 ? $stage['count'] : ''); ?>

                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Throughput Produksi (<?php echo e(count($throughput)); ?> Hari Terakhir)
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-end gap-2 h-32">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $throughput; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $height = $maxThroughput > 0 ? ($day['count'] / $maxThroughput * 100) : 0; ?>
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                            <?php echo e($day['count'] > 0 ? $day['count'] : ''); ?>

                        </span>
                        <div class="w-full rounded-t-md transition-all duration-500 <?php echo e($day['count'] > 0 ? 'bg-primary-500' : 'bg-gray-100 dark:bg-gray-700'); ?>"
                            style="height: <?php echo e(max($height, $day['count'] > 0 ? 8 : 4)); ?>%">
                        </div>
                        <span class="text-xs text-gray-400"><?php echo e($day['date']); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <p class="text-xs text-gray-400 mt-2 text-center">
                Jumlah log "COMPLETED" per hari
            </p>
        </div>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        
        <div>
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Ringkasan Pembayaran
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500">Total Revenue</span>
                    <span class="font-bold text-gray-900 dark:text-white">
                        Rp <?php echo e(number_format($paymentStats['total_revenue'] ?? 0, 0, ',', '.')); ?>

                    </span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-500">Sudah Terkumpul</span>
                    <span class="font-bold text-green-600">
                        Rp <?php echo e(number_format($paymentStats['total_collected'] ?? 0, 0, ',', '.')); ?>

                    </span>
                </div>
                <div class="grid grid-cols-3 gap-3 mt-2">
                    <div class="text-center p-3 bg-red-50 dark:bg-red-950 rounded-lg">
                        <div class="text-xl font-bold text-red-600"><?php echo e($paymentStats['unpaid']['count'] ?? 0); ?></div>
                        <div class="text-xs text-red-500">Belum Bayar</div>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-950 rounded-lg">
                        <div class="text-xl font-bold text-yellow-600"><?php echo e($paymentStats['dp']['count'] ?? 0); ?></div>
                        <div class="text-xs text-yellow-500">DP</div>
                    </div>
                    <div class="text-center p-3 bg-green-50 dark:bg-green-950 rounded-lg">
                        <div class="text-xl font-bold text-green-600"><?php echo e($paymentStats['lunas']['count'] ?? 0); ?></div>
                        <div class="text-xs text-green-500">Lunas</div>
                    </div>
                </div>
            </div>
        </div>

        
        <div>
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Stasiun Paling Sering Blocked (30 Hari)
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bottleneckStages->isEmpty()): ?>
                    <div class="p-6 text-center text-gray-400 text-sm">
                        ✅ Tidak ada bottleneck tercatat!
                    </div>
                <?php else: ?>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Stasiun</th>
                                <th class="px-4 py-3 text-right">Blocked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $bottleneckStages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 flex items-center gap-2">
                                        <span class="text-red-400 font-bold text-xs">#<?php echo e($i+1); ?></span>
                                        <span class="text-gray-700 dark:text-gray-300"><?php echo e($stage->stage?->name ?? 'Tidak diketahui'); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                            <?php echo e($stage->blocked_count); ?>x
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            Leaderboard Petugas Produksi (30 Hari Terakhir)
        </h2>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($personnelKpi->isEmpty()): ?>
                <div class="p-6 text-center text-gray-400 text-sm">
                    Belum ada data progres dalam 30 hari terakhir.
                </div>
            <?php else: ?>
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
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $personnelKpi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $kpi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $maxLog = $personnelKpi->max('log_count') ?: 1;
                                $pct = $kpi->log_count / $maxLog * 100;
                            ?>
                            <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-4 py-3 font-bold text-gray-400"><?php echo e($i + 1); ?></td>
                                <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">
                                    <?php echo e($kpi->personnel?->name ?? 'N/A'); ?>

                                </td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">
                                    <?php echo e($kpi->log_count); ?>

                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-green-600 font-medium"><?php echo e($kpi->completed_count); ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="<?php echo e($kpi->blocked_count > 0 ? 'text-red-500 font-medium' : 'text-gray-400'); ?>">
                                        <?php echo e($kpi->blocked_count); ?>

                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                        <div class="bg-primary-500 h-2 rounded-full transition-all duration-500"
                                            style="width: <?php echo e($pct); ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <p class="text-xs text-gray-400 text-right">
        Auto-refresh setiap 30 detik · Data diambil secara realtime dari database
    </p>
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
<?php /**PATH D:\Project\erplrv\resources\views/filament/pages/performance-board.blade.php ENDPATH**/ ?>