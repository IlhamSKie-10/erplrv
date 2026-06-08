<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ProductionQueueCode;

class Product extends Model
{
    protected $table = 'products';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code', 'name', 'category_id', 'product_type_id', 'product_model_id',
        'lead_time_days', 'base_production_minutes', 'production_queue', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'production_queue'        => ProductionQueueCode::class,
            'is_active'               => 'boolean',
            'lead_time_days'          => 'integer',
            'base_production_minutes' => 'integer',
            'created_at'              => 'datetime',
            'updated_at'              => 'datetime',
            'deleted_at'              => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_model_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'product_id');
    }
}
