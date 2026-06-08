<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\ProductionQueueCode;

class ProductionQueue extends Model
{
    use HasUuids;
    protected $table = 'production_queues';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['code', 'name'];

    protected function casts(): array
    {
        return ['code' => ProductionQueueCode::class];
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(ProductionWorkOrder::class, 'queue_id');
    }
}
