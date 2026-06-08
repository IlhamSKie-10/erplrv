<?php

namespace App\Observers;

use App\Models\DesignTask;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\DesignStatus;
use App\Services\NotificationService;

class DesignTaskObserver
{
    public function updated(DesignTask $designTask): void
    {
        if ($designTask->isDirty('status') && $designTask->status?->value === 'ACC') {
            
            // Set design_acc_at if null
            if (empty($designTask->design_acc_at)) {
                $designTask->design_acc_at = now();
                $designTask->saveQuietly();
            }

            // Update Order Status
            $order = $designTask->order;
            if ($order) {
                $order->update([
                    'design_status' => DesignStatus::ACC,
                    'status' => OrderStatus::DESIGN_APPROVED,
                ]);

                // Notify Production
                app(NotificationService::class)->sendByRole(
                    'Desain Disetujui',
                    "Pesanan {$order->order_code} telah di-ACC desainnya dan siap diproduksi.",
                    'PRODUCTION',
                    $order->id,
                    'INFO'
                );
            }
        }
    }
}
