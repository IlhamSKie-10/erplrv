<?php

namespace App\Livewire\CS;

use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;
use Livewire\WithPagination;

class OrderTable extends Component
{
    use WithPagination;

    // ─── Filters ──────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';
    public string $paymentFilter = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';

    // ─── Modal state ──────────────────────────────
    public bool $showForm = false;
    public ?string $editingOrderId = null;

    protected $listeners = [
        'orderSaved'   => 'handleOrderSaved',
        'closeForm'    => 'closeForm',
    ];

    // Reset pagination when filters change
    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedPaymentFilter(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->editingOrderId = null;
        $this->showForm = true;
    }

    public function openEdit(string $orderId): void
    {
        $this->editingOrderId = $orderId;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingOrderId = null;
    }

    public function handleOrderSaved(): void
    {
        $this->closeForm();
        $this->dispatch('notify', message: 'Pesanan berhasil disimpan.', type: 'success');
    }

    public function submit(string $orderId): void
    {
        try {
            $service = app(OrderService::class);
            $service->submitOrder($orderId, auth()->id());
            $this->dispatch('notify', message: 'Pesanan berhasil disubmit ke desainer.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function duplicate(string $orderId): void
    {
        try {
            $service = app(OrderService::class);
            $new = $service->duplicateOrder($orderId, auth()->id());
            $this->dispatch('notify', message: 'Pesanan berhasil diduplikat: ' . $new->order_code, type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function delete(string $orderId): void
    {
        try {
            $service = app(OrderService::class);
            $service->softDelete($orderId, auth()->id());
            $this->dispatch('notify', message: 'Pesanan dihapus.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $query = Order::whereNull('deleted_at')
            ->with([
                'account',
                'product',
                'productType',
                'orderSource',
                'createdBy',
                'designTasks' => fn ($q) => $q->select('id', 'order_id', 'design_acc_at', 'status'),
            ]);

        if ($this->search) {
            $search = '%' . $this->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', $search)
                  ->orWhereHas('account', fn ($a) => $a->where('name', 'like', $search));
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->paymentFilter) {
            $query->where('payment_status', $this->paymentFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('deadline_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('deadline_at', '<=', $this->dateTo);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('livewire.cs.order-table', [
            'orders' => $orders,
        ]);
    }
}
