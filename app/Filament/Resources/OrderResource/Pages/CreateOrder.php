<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\CustomerAccount;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

use App\Models\Order;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Form;

class CreateOrder extends \Filament\Resources\Pages\Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = OrderResource::class;
    protected string $view = 'filament.resources.order-resource.pages.cs-workspace';
    
    public ?array $orderData = [];
    public ?string $editingOrderId = null;

    protected array $stickyFields = [];

    public function mount(): void
    {
        $this->form->fill($this->getFormDefaults());
    }

    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema(\App\Filament\Resources\OrderResource\Schema\OrderFormSchema::getSchema())
            ->statePath('orderData')
            ->model(Order::class);
    }

    public function table(Table $table): Table
    {
        // Re-use table from OrderResource, but adjust actions for this workspace context
        return OrderResource::table($table)
            ->query(Order::query()->latest())
            ->actions([
                \Filament\Actions\Action::make('edit_workspace')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->action(fn (Order $record) => $this->loadOrderForEditing($record))
                    ->visible(fn (Order $record) => in_array($record->status->value ?? $record->status, ['DRAFT'])),
                    
                \Filament\Actions\Action::make('replicate_workspace')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(fn (Order $record) => $this->replicateOrderToForm($record)),
                    
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn (Order $record) => in_array($record->status->value ?? $record->status, ['DRAFT'])),
            ]);
    }

    public function loadOrderForEditing(Order $order)
    {
        $this->editingOrderId = $order->id;
        $data = $order->attributesToArray();
        // Load relationships and virtual fields
        if ($order->order_source_id) {
            $data['order_source_name'] = \App\Models\OrderSource::find($order->order_source_id)?->name;
        }
        if ($order->account_id) {
            $account = \App\Models\CustomerAccount::find($order->account_id);
            $data['account_id'] = $order->account_id;
            $data['_temp_account_name'] = $account?->name;
            $data['customer_phone'] = $account?->phone;
        }
        if ($order->product_type_id) {
            $data['product_type_name'] = \App\Models\ProductType::find($order->product_type_id)?->name;
        }
        if ($order->product_id) {
            $data['product_name'] = \App\Models\Product::find($order->product_id)?->name;
        }
        
        $snapshot = $order->form_snapshot ?? [];
        $data['_production_notes'] = $snapshot['_production_notes'] ?? null;
        $data['_reference_link'] = $snapshot['_reference_link'] ?? null;
        $data['_special_requests'] = $snapshot['_special_requests'] ?? null;

        $this->form->fill($data);
    }

    public function replicateOrderToForm(Order $order)
    {
        $this->editingOrderId = null;
        $this->loadOrderForEditing($order);
        $this->editingOrderId = null; // Ensure it's treated as new
        
        // Reset specific fields for the new replicated order
        $data = $this->form->getState();
        $data['status'] = 'DRAFT';
        $data['payment_status'] = \App\Enums\PaymentStatus::UNPAID->value;
        $data['amount_paid'] = 0;
        $this->form->fill($data);

        \Filament\Notifications\Notification::make()->title('Data disalin ke form')->success()->send();
    }

    public function updated($name, $value)
    {
        if (str_starts_with($name, 'orderData.')) {
            $this->saveDraft();
        }
    }

    public function saveDraft()
    {
        // Gunakan getRawState() agar tidak men-trigger validasi agresif 
        // yang menyebabkan layar autoscroll saat CS baru mengetik.
        $data = $this->form->getRawState();
        
        // Mencegah error database: jangan autosave jika kolom wajib belum terisi
        if (
            empty($data['account_id']) || 
            empty($data['order_source_name']) || 
            empty($data['product_type_name']) || 
            empty($data['product_name'])
        ) {
            return; // Batalkan autosave jika data belum cukup
        }

        try {
            $this->processAndSaveData($data, 'DRAFT');
        } catch (\Illuminate\Database\QueryException $e) {
            // Abaikan error constraint database (misal: kolom wajib belum diisi)
            // Autosave akan berhasil di percobaan berikutnya saat data sudah lengkap.
        }
    }

    public function saveOrder()
    {
        // Saat disave manual, jalankan validasi
        $data = $this->form->getState();
        $this->processAndSaveData($data, 'DRAFT');
        
        // Reset form but keep sticky fields
        $this->editingOrderId = null;
        $this->form->fill($this->getFormDefaults());
        
        \Filament\Notifications\Notification::make()
            ->title('✅ Pesanan berhasil disimpan!')
            ->success()
            ->send();
    }

    protected function processAndSaveData(array $data, string $defaultStatus)
    {
        $data['status'] = $data['status'] ?? $defaultStatus;

        if (!empty($data['order_source_name'])) {
            $source = \App\Models\OrderSource::firstOrCreate(
                ['name' => $data['order_source_name']],
                ['id' => \Illuminate\Support\Str::uuid()->toString(), 'code' => strtoupper(\Illuminate\Support\Str::slug($data['order_source_name']))]
            );
            $data['order_source_id'] = $source->id;
        }

        // account_id is natively handled by the relationship select with createOptionForm.

        if (!empty($data['product_type_name'])) {
            $pt = \App\Models\ProductType::firstOrCreate(
                ['name' => $data['product_type_name']],
                ['id' => \Illuminate\Support\Str::uuid()->toString(), 'is_active' => true, 'code' => strtoupper(\Illuminate\Support\Str::slug($data['product_type_name']))]
            );
            $data['product_type_id'] = $pt->id;
        }

        if (!empty($data['product_name']) && !empty($data['product_type_id'])) {
            $product = \App\Models\Product::firstOrCreate(
                [
                    'name' => $data['product_name'],
                    'product_type_id' => $data['product_type_id']
                ],
                ['id' => \Illuminate\Support\Str::uuid()->toString(), 'is_active' => true, 'code' => strtoupper(\Illuminate\Support\Str::slug($data['product_name']))]
            );
            $data['product_id'] = $product->id;
        }

        if (empty($data['product_sentence'])) {
            $productName = $data['product_name'] ?? '';
            $accountName = $data['_temp_account_name'] ?? '';
            $sizeP       = $data['_size_p'] ?? '';
            $sizeL       = $data['_size_l'] ?? '';
            $size        = ($sizeP && $sizeL) ? "{$sizeP}x{$sizeL} cm" : '';
            $text        = $data['_text'] ?? '';
            $color       = $data['_color'] ?? '';
            $shape       = $data['_shape'] ?? '';
            $variant     = $data['_variant'] ?? '';
            $lamp        = $data['_lamp'] ?? '';
            $bracket     = $data['_bracket'] ?? '';
            $extras      = implode('/', array_filter([$color, $shape, $variant]));

            $parts = array_filter([$accountName, $productName, $size, $text, $extras, $lamp, $bracket]);
            $data['product_sentence'] = implode(' - ', $parts) ?: '-';
        }

        $notes = [];
        if (!empty($data['_production_notes'])) $notes[] = "Catatan Produksi:\n" . $data['_production_notes'];
        if (!empty($data['_reference_link'])) $notes[] = "Link Referensi:\n" . $data['_reference_link'];
        if (!empty($data['_special_requests'])) $notes[] = "Permintaan Khusus:\n" . $data['_special_requests'];
        $data['admin_notes'] = implode("\n\n", $notes) ?: null;

        $snapshot = $data['form_snapshot'] ?? [];
        $snapshot['_production_notes'] = $data['_production_notes'] ?? null;
        $snapshot['_reference_link'] = $data['_reference_link'] ?? null;
        $snapshot['_special_requests'] = $data['_special_requests'] ?? null;
        $data['form_snapshot'] = $snapshot;

        unset($data['_size_p'], $data['_size_l'], $data['_text'], $data['_color'],
              $data['_variant'], $data['_shape'], $data['_bracket'], $data['_lamp'],
              $data['account_name'], $data['customer_phone'], $data['product_type_name'], $data['product_name'], $data['order_source_name'], 
              $data['_production_notes'], $data['_reference_link'], $data['_special_requests'], $data['_admin_label']);

        if ($this->editingOrderId) {
            $order = Order::find($this->editingOrderId);
            if ($order) {
                $order->update($data);
            }
        } else {
            $data['created_by_id'] = auth()->id();
            if (empty($data['order_code'])) {
                $data['order_code'] = app(\App\Services\OrderService::class)->generateOrderCode();
            }
            $order = Order::create($data);
            $this->editingOrderId = $order->id; // Lock it so next autosave updates it
        }
        
        // Handle notifications and statuses via observer or directly here
        if ($data['status'] !== 'DRAFT') {
            app(\App\Services\OrderService::class)->submitOrder($order->id, auth()->id());
        }
    }

    protected function getFormDefaults(): array
    {
        return [
            'status'          => 'DRAFT',
            'amount_paid'     => 0,
        ];
    }
}
