<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\{DesignTaskStatus, PrintStickerOption};

class DesignTask extends Model
{
    protected $table = 'design_tasks';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id', 'assigned_designer_id', 'status',
        'design_acc_at', 'forwarded_at', 'print_sticker', 'cut_methods',
    ];

    protected function casts(): array
    {
        return [
            'status'        => DesignTaskStatus::class,
            'print_sticker' => PrintStickerOption::class,
            'cut_methods'   => 'array',  // JSONB → PHP array of CutMethod strings
            'design_acc_at' => 'datetime',
            'forwarded_at'  => 'datetime',
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function assignedDesigner(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'assigned_designer_id');
    }
}
