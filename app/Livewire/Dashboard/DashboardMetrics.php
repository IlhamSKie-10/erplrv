<?php

namespace App\Livewire\Dashboard;

use App\Models\DesignTask;
use App\Models\Notification as ErpNotification;
use App\Models\NotificationRecipient;
use App\Models\Order;
use App\Models\ProductionWorkOrder;
use App\Enums\OrderStatus;
use Livewire\Component;

class DashboardMetrics extends Component
{
    // Polling is handled at the view level with wire:poll.15s

    public function render()
    {
        $userId = auth()->id();

        $orderCounts = Order::whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $productionActive = ProductionWorkOrder::whereNotIn('status', ['DONE', 'COMPLETED'])
            ->whereHas('order', fn ($q) => $q->whereNull('deleted_at'))
            ->count();

        $designPending = DesignTask::where('status', 'PROCESS')->count();

        $unreadNotifications = NotificationRecipient::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $overdueOrders = Order::whereNull('deleted_at')
            ->whereNotIn('status', ['COMPLETED', 'SHIPPED', 'CANCELLED'])
            ->where('deadline_at', '<', now())
            ->count();

        $todayOrders = Order::whereNull('deleted_at')
            ->whereDate('created_at', today())
            ->count();

        return view('livewire.dashboard.dashboard-metrics', [
            'orderCounts'         => $orderCounts,
            'productionActive'    => $productionActive,
            'designPending'       => $designPending,
            'unreadNotifications' => $unreadNotifications,
            'overdueOrders'       => $overdueOrders,
            'todayOrders'         => $todayOrders,
            'totalOrders'         => Order::whereNull('deleted_at')->count(),
        ]);
    }
}
