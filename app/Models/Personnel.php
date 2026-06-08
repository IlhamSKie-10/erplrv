<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personnel extends Model
{
    protected $table = 'personnel';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'code',
        'full_name',
        'division',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(ProductionProgressLog::class, 'personnel_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(ProductionWorkOrder::class, 'assigned_personnel_id');
    }

    public function designTasks(): HasMany
    {
        return $this->hasMany(DesignTask::class, 'assigned_designer_id');
    }
}
