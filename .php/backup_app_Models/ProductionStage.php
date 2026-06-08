<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ProductionStageCode;

class ProductionStage extends Model
{
    protected $table = 'production_stages';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'sort_order',
        'default_estimated_minutes', 'requires_previous_stage',
    ];

    protected function casts(): array
    {
        return [
            'code'                       => ProductionStageCode::class,
            'sort_order'                 => 'integer',
            'default_estimated_minutes'  => 'integer',
            'requires_previous_stage'    => 'boolean',
        ];
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(ProductionWorkOrder::class, 'current_stage_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProductionProgressLog::class, 'stage_id');
    }
}
