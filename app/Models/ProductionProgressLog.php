<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ProgressStatus;

class ProductionProgressLog extends Model
{
    protected $table = 'production_progress_logs';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // only has created_at

    protected $fillable = [
        'work_order_id', 'stage_id', 'personnel_id',
        'status', 'note', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => ProgressStatus::class,
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
            'created_at'   => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionWorkOrder::class, 'work_order_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProductionStage::class, 'stage_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }
}
