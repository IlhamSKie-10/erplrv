<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\{
    OrderStatus, PaymentType, PaymentStatus, DesignStatus,
    PackingType, JobComplexity
};

class Order extends Model
{
    protected $table = 'orders';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_code', 'timestamp', 'created_by_id', 'order_source_id',
        'account_id', 'product_id', 'product_model_id', 'product_type_id',
        'city', 'expedition_id', 'deadline_at', 'complexity', 'status',
        'payment_type', 'total_order', 'payment_status', 'amount_paid',
        'design_status', 'packing_type', 'product_sentence', 'admin_notes',
        'form_snapshot', 'version', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'form_snapshot'  => 'array',
            'total_order'    => 'decimal:2',
            'amount_paid'    => 'decimal:2',
            'status'         => OrderStatus::class,
            'payment_type'   => PaymentType::class,
            'payment_status' => PaymentStatus::class,
            'design_status'  => DesignStatus::class,
            'packing_type'   => PackingType::class,
            'complexity'     => JobComplexity::class,
            'version'        => 'integer',
            'deadline_at'    => 'datetime',
            'submitted_at'   => 'datetime',
            'timestamp'      => 'datetime',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
            'deleted_at'     => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function orderSource(): BelongsTo
    {
        return $this->belongsTo(OrderSource::class, 'order_source_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class, 'account_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_model_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'expedition_id');
    }

    public function designTasks(): HasMany
    {
        return $this->hasMany(DesignTask::class, 'order_id')->orderBy('created_at');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(ProductionWorkOrder::class, 'order_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'order_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'order_id');
    }
}
