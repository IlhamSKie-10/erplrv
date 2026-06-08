<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Product;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width|string|null
    {
        return 'full';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['order_source_id'])) {
            $data['order_source_name'] = \App\Models\OrderSource::find($data['order_source_id'])?->name;
        }
        if (!empty($data['account_id'])) {
            $account = \App\Models\CustomerAccount::find($data['account_id']);
            $data['account_name'] = $account?->name;
            $data['customer_phone'] = $account?->phone;
        }
        if (!empty($data['product_type_id'])) {
            $data['product_type_name'] = \App\Models\ProductType::find($data['product_type_id'])?->name;
        }
        if (!empty($data['product_id'])) {
            $data['product_name'] = \App\Models\Product::find($data['product_id'])?->name;
        }
        
        $snapshot = $data['form_snapshot'] ?? [];
        $data['_production_notes'] = $snapshot['_production_notes'] ?? null;
        $data['_reference_link'] = $snapshot['_reference_link'] ?? null;
        $data['_special_requests'] = $snapshot['_special_requests'] ?? null;
        
        // Restore spec fields from product_sentence for editing
        // (Best-effort: parse from the saved sentence)
        // The virtual fields (_size_p, _text, etc.) will be blank by default;
        // users can re-fill them to update the sentence.
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['order_source_name'])) {
            $source = \App\Models\OrderSource::firstOrCreate(['name' => $data['order_source_name']]);
            $data['order_source_id'] = $source->id;
        }
        if (!empty($data['account_name'])) {
            $account = \App\Models\CustomerAccount::firstOrCreate(['name' => $data['account_name']]);
            if (!empty($data['customer_phone']) && empty($account->phone)) {
                $account->update(['phone' => $data['customer_phone']]);
            } elseif (!empty($data['customer_phone']) && $account->phone !== $data['customer_phone']) {
                $account->update(['phone' => $data['customer_phone']]);
            }
            $data['account_id'] = $account->id;
        }

        if (!empty($data['product_type_name'])) {
            $pt = \App\Models\ProductType::firstOrCreate(
                ['name' => $data['product_type_name']],
                ['is_active' => true]
            );
            $data['product_type_id'] = $pt->id;
        }

        if (!empty($data['product_name']) && !empty($data['product_type_id'])) {
            $product = \App\Models\Product::firstOrCreate(
                [
                    'name' => $data['product_name'],
                    'product_type_id' => $data['product_type_id']
                ],
                ['is_active' => true]
            );
            $data['product_id'] = $product->id;
        }

        // Rebuild product_sentence if spec fields are present
        if (!empty(array_filter([
            $data['_size_p'] ?? null,
            $data['_text']   ?? null,
            $data['_color']  ?? null,
        ]))) {
            $productName = $data['product_name'] ?? '';
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
            $accountName = $data['account_name'] ?? '';

            $parts = array_filter([$accountName, $productName, $size, $text, $extras, $lamp, $bracket]);
            if (!empty($parts)) {
                $data['product_sentence'] = implode(' - ', $parts);
            }
        }

        // Build admin_notes and save raw fields to snapshot
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

        // Strip virtual fields
        unset($data['_size_p'], $data['_size_l'], $data['_text'], $data['_color'],
              $data['_variant'], $data['_shape'], $data['_bracket'], $data['_lamp'],
              $data['account_name'], $data['customer_phone'], $data['product_type_name'], $data['product_name'], $data['order_source_name'],
              $data['_production_notes'], $data['_reference_link'], $data['_special_requests'], $data['_admin_label']);

        return $data;
    }
}
