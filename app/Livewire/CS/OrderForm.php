<?php

namespace App\Livewire\CS;

use App\Models\Carrier;
use App\Models\CustomerAccount;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class OrderForm extends Component
{
    public ?string $orderId  = null;
    public int     $version  = 1;

    // ─── Autosave status ──────────────────────────
    public string $autosaveStatus = 'idle'; // idle | saving | synced

    // ─── Form fields ──────────────────────────────
    public string  $order_source_id  = '';
    public string  $account_name     = '';
    public string  $product_type_id  = '';
    public string  $product_id       = '';
    public string  $model_id         = '';
    public string  $deadline_at      = '';
    public string  $city             = '';
    public string  $expedition_id    = '';
    public string  $payment_type     = 'SPL';
    public string  $total_order      = '0';
    public string  $payment_status   = 'UNPAID';
    public string  $amount_paid      = '0';
    public string  $design_status    = 'PROCESS';
    public string  $packing_type     = 'BUBBLE';
    public string  $product_sentence = '';
    public string  $admin_notes      = '';
    public string  $status           = 'DRAFT';

    // ─── Snapshot fields ──────────────────────────
    public string $size            = '';
    public string $variant         = '';
    public string $shape           = '';
    public string $bracket         = '';
    public string $lamp            = '';
    public string $text            = '';
    public string $color           = '';
    public string $productionNotes = '';
    public string $referenceLink   = '';
    public string $specialRequest  = '';

    protected function rules(): array
    {
        return [
            'account_name'     => 'required|string|max:255',
            'order_source_id'  => 'required|string',
            'product_type_id'  => 'required|string',
            'product_id'       => 'required|string',
            'deadline_at'      => 'required|date|after:today',
            'payment_type'     => 'required|in:SPL,COD,NON_COD',
            'total_order'      => 'required|numeric|min:0',
            'payment_status'   => 'required|in:UNPAID,DP,LUNAS',
            'amount_paid'      => 'nullable|numeric|min:0',
            'packing_type'     => 'required|in:BUBBLE,TRIPLEK,KAYU',
            'product_sentence' => 'required|string',
            'design_status'    => 'required|in:PROCESS,ACC',
            'status'           => 'required|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'account_name.required'    => 'Nama akun pelanggan wajib diisi.',
            'order_source_id.required' => 'Sumber order wajib dipilih.',
            'product_type_id.required' => 'Tipe produk wajib dipilih.',
            'product_id.required'      => 'Produk wajib dipilih.',
            'deadline_at.required'     => 'Deadline wajib diisi.',
            'deadline_at.after'        => 'Deadline harus tanggal yang akan datang.',
            'total_order.min'          => 'Total order tidak boleh negatif.',
            'product_sentence.required' => 'Ringkasan produk wajib tersedia.',
        ];
    }

    public function mount(?string $orderId = null): void
    {
        if ($orderId) {
            $this->orderId = $orderId;
            $this->loadOrder($orderId);
        } else {
            $this->deadline_at = now()->addDays(3)->format('Y-m-d');
        }
    }

    private function loadOrder(string $orderId): void
    {
        $order = Order::with([
            'account', 'product', 'productType', 'productModel',
            'orderSource', 'expedition',
        ])->findOrFail($orderId);

        $this->version          = $order->version;
        $this->order_source_id  = $order->orderSource?->code ?? '';
        $this->account_name     = $order->account?->name ?? '';
        $this->product_type_id  = $order->productType?->code ?? '';
        $this->product_id       = $order->product?->code ?? '';
        $this->model_id         = $order->productModel?->code ?? '';
        $this->deadline_at      = $order->deadline_at?->format('Y-m-d') ?? '';
        $this->city             = $order->city ?? '';
        $this->expedition_id    = $order->expedition?->code ?? '';
        $this->payment_type     = $order->payment_type?->value ?? 'SPL';
        $this->total_order      = (string) $order->total_order;
        $this->payment_status   = $order->payment_status?->value ?? 'UNPAID';
        $this->amount_paid      = (string) $order->amount_paid;
        $this->design_status    = $order->design_status?->value ?? 'PROCESS';
        $this->packing_type     = $order->packing_type?->value ?? 'BUBBLE';
        $this->product_sentence = $order->product_sentence ?? '';
        $this->admin_notes      = $order->admin_notes ?? '';
        $this->status           = $order->status?->value ?? 'DRAFT';

        // Load form snapshot
        $snap = $order->form_snapshot ?? [];
        $this->size            = $snap['size'] ?? '';
        $this->variant         = $snap['variant'] ?? '';
        $this->shape           = $snap['shape'] ?? '';
        $this->bracket         = $snap['bracket'] ?? '';
        $this->lamp            = $snap['lamp'] ?? '';
        $this->text            = $snap['text'] ?? '';
        $this->color           = $snap['color'] ?? '';
        $this->productionNotes = $snap['productionNotes'] ?? '';
        $this->referenceLink   = $snap['referenceLink'] ?? '';
        $this->specialRequest  = $snap['specialRequest'] ?? '';

        $this->autosaveStatus = 'synced';
    }

    /**
     * Build the input array for OrderService from current component state.
     */
    private function buildInput(): array
    {
        return [
            'id'               => $this->orderId,
            'version'          => $this->version,
            'order_source_id'  => $this->order_source_id,
            'account_name'     => $this->account_name,
            'product_type_id'  => $this->product_type_id,
            'product_id'       => $this->product_id,
            'model_id'         => $this->model_id ?: null,
            'deadline_at'      => $this->deadline_at,
            'city'             => $this->city ?: null,
            'expedition_id'    => $this->expedition_id ?: null,
            'payment_type'     => $this->payment_type,
            'total_order'      => $this->total_order,
            'payment_status'   => $this->payment_status,
            'amount_paid'      => $this->amount_paid ?: 0,
            'design_status'    => $this->design_status,
            'packing_type'     => $this->packing_type,
            'product_sentence' => $this->product_sentence,
            'admin_notes'      => $this->admin_notes ?: null,
            'status'           => $this->status,
            // snapshot
            'size'             => $this->size ?: null,
            'variant'          => $this->variant ?: null,
            'shape'            => $this->shape ?: null,
            'bracket'          => $this->bracket ?: null,
            'lamp'             => $this->lamp ?: null,
            'text'             => $this->text ?: null,
            'color'            => $this->color ?: null,
            'productionNotes'  => $this->productionNotes ?: null,
            'referenceLink'    => $this->referenceLink ?: null,
            'specialRequest'   => $this->specialRequest ?: null,
        ];
    }

    /**
     * Autosave — called from Alpine.js debounce (900ms).
     * Lenient: saves without full validation, only requires minimum fields.
     */
    public function autosave(): void
    {
        // Require minimum data before saving
        if (
            empty(trim($this->account_name)) ||
            empty($this->order_source_id) ||
            empty($this->product_type_id) ||
            empty($this->product_id)
        ) {
            $this->autosaveStatus = 'idle';
            return;
        }

        $this->autosaveStatus = 'saving';

        try {
            $service = app(OrderService::class);
            $result  = $service->saveOrderDraft($this->buildInput(), auth()->id());

            // Persist the new order ID if this was a create
            if (empty($this->orderId) && !empty($result['order_id'])) {
                $this->orderId = $result['order_id'];
            }

            // Update version counter for optimistic locking
            if (!empty($result['version'])) {
                $this->version = $result['version'];
            }

            $this->autosaveStatus = 'synced';
        } catch (\RuntimeException) {
            // Version conflict — reload to get latest version
            if ($this->orderId) {
                try {
                    $this->loadOrder($this->orderId);
                } catch (\Throwable) {
                }
            }
            $this->autosaveStatus = 'idle';
        } catch (\Throwable) {
            $this->autosaveStatus = 'idle';
        }
    }

    /**
     * Manual save — full validation, used by "Simpan Draft" button.
     */
    public function save(): void
    {
        $this->validate();

        try {
            $service = app(OrderService::class);
            $result  = $service->saveOrderDraft($this->buildInput(), auth()->id());

            if (empty($this->orderId) && !empty($result['order_id'])) {
                $this->orderId = $result['order_id'];
            }

            if (!empty($result['version'])) {
                $this->version = $result['version'];
            }

            $this->autosaveStatus = 'synced';
            $this->dispatch('orderSaved');
        } catch (\RuntimeException $e) {
            $this->addError('version', $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('form', 'Terjadi kesalahan. Coba lagi.');
        }
    }

    public function render()
    {
        $service  = app(OrderService::class);
        $accounts = CustomerAccount::orderBy('name')->pluck('name');
        $carriers = Carrier::orderBy('name')->get(['id', 'code', 'name']);
        $cities   = Order::whereNotNull('city')
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->filter(fn ($c) => trim((string) $c) !== '')
            ->values();

        return view('livewire.cs.order-form', [
            'accounts'      => $accounts,
            'carriers'      => $carriers,
            'cities'        => $cities,
            'catalogConfig' => $service->getCatalogConfig(),
        ]);
    }
}
