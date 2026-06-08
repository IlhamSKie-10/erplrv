<?php

namespace App\Observers;

use App\Models\ProductionWorkOrder;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Services\NotificationService;

class ProductionWorkOrderObserver
{
    public function updated(ProductionWorkOrder $workOrder): void
    {
        if ($workOrder->isDirty('status') && $workOrder->status?->value === 'DONE') {
            
            // Set completion_date if null
            if (empty($workOrder->completion_date)) {
                $workOrder->completion_date = now();
                $workOrder->saveQuietly();
            }

            // Update Order Status to READY_TO_SHIP or COMPLETED
            $order = $workOrder->order;
            if ($order) {
                $order->update([
                    'status' => OrderStatus::READY_TO_SHIP,
                ]);

                // Notify CS
                app(NotificationService::class)->sendByRole(
                    'Produksi Selesai',
                    "Pesanan {$order->order_code} telah selesai diproduksi dan siap dikirim.",
                    'CS',
                    $order->id,
                    'INFO'
                );
            }
        }
    }
}
