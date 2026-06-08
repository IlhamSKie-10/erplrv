<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveOrderDraftRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'order_source_id' => $this->input('order_source_id', $this->input('orderSourceId')),
            'account_name' => $this->input('account_name', $this->input('accountName')),
            'product_id' => $this->input('product_id', $this->input('productId')),
            'product_type_id' => $this->input('product_type_id', $this->input('productTypeId')),
            'model_id' => $this->input('model_id', $this->input('modelId')),
            'deadline_at' => $this->input('deadline_at', $this->input('deadlineAt')),
            'payment_type' => $this->input('payment_type', $this->input('paymentType')),
            'payment_status' => $this->input('payment_status', $this->input('paymentStatus')),
            'design_status' => $this->input('design_status', $this->input('designStatus')),
            'packing_type' => $this->input('packing_type', $this->input('packingType')),
            'product_sentence' => $this->input('product_sentence', $this->input('productSentence')),
            'admin_notes' => $this->input('admin_notes', $this->input('adminNotes')),
            'expedition_id' => $this->input('expedition_id', $this->input('expeditionId')),
            'production_queue' => $this->input('production_queue', $this->input('productionQueue')),
            'production_notes' => $this->input('production_notes', $this->input('productionNotes')),
            'reference_link' => $this->input('reference_link', $this->input('referenceLink')),
            'special_request' => $this->input('special_request', $this->input('specialRequest')),
            'amount_paid' => $this->input('amount_paid', $this->input('amountPaid')),
            'total_order' => $this->input('total_order', $this->input('totalOrder')),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by CheckRole middleware on the route
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_source_id' => 'required|string',
            'account_name'    => 'required|string',
            'product_id'      => 'required|string',
            'product_type_id' => 'required|string',
            'model_id'        => 'nullable|string',
            'deadline_at'     => 'required|date',
            'status'          => 'required|string',
            'payment_type'    => 'required|string',
            'total_order'     => 'required|numeric|min:0',
            'payment_status'  => 'required|string',
            'amount_paid'     => 'required|numeric|min:0',
            'design_status'   => 'required|string',
            'packing_type'    => 'required|string',
            'product_sentence'=> 'required|string',
            'admin_notes'     => 'nullable|string',
            'expedition_id'   => 'nullable|string',
            'city'            => 'nullable|string',
            'production_queue'=> 'nullable|string',
            
            // Snapshot fields
            'size'            => 'nullable|string',
            'variant'         => 'nullable|string',
            'shape'           => 'nullable|string',
            'bracket'         => 'nullable|string',
            'lamp'            => 'nullable|string',
            'production_notes'=> 'nullable|string',
            'productionNotes' => 'nullable|string',
            'reference_link'  => 'nullable|string',
            'referenceLink' => 'nullable|string',
            'special_request' => 'nullable|string',
            'specialRequest' => 'nullable|string',
            'text'            => 'nullable|string',
            'color'           => 'nullable|string',
            'designStatus'    => 'nullable|string',
            'paymentType'     => 'nullable|string',
            'paymentStatus'   => 'nullable|string',
            'packingType'     => 'nullable|string',
            'productSentence' => 'nullable|string',
            'orderSourceId'   => 'nullable|string',
            'accountName'     => 'nullable|string',
            'productId'       => 'nullable|string',
            'productTypeId'   => 'nullable|string',
            'modelId'         => 'nullable|string',
            'deadlineAt'      => 'nullable|date',
            'adminNotes'      => 'nullable|string',
            'expeditionId'    => 'nullable|string',
            'productionQueue' => 'nullable|string',
            'amountPaid'      => 'nullable|numeric|min:0',
            'totalOrder'      => 'nullable|numeric|min:0',
        ];
    }
}
