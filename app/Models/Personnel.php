<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Personnel extends Model
{
    use HasUuids;
    protected $table = 'personnel';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'code',
        'full_name',
        'division',
        'production_team',
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

    protected static function booted(): void
    {
        static::creating(function (Personnel $personnel) {
            if (empty($personnel->code) || $personnel->code === '-') {
                $prefix = 'EMP';
                if ($personnel->division === 'Management') $prefix = 'MNG';
                elseif ($personnel->division === 'Design') $prefix = 'DSG';
                elseif ($personnel->division === 'CS') $prefix = 'CS';
                elseif ($personnel->division === 'Production') {
                    if ($personnel->production_team === 'Advertising 1') $prefix = 'ADV1';
                    elseif ($personnel->production_team === 'Advertising 2') $prefix = 'ADV2';
                    elseif ($personnel->production_team === 'Homedecor') $prefix = 'HMD';
                    else $prefix = 'PRD';
                }
                elseif ($personnel->division === 'IT') $prefix = 'IT';
    
                // Hitung urutan berdasarkan divisi
                $count = static::where('division', $personnel->division)->count() + 1;
                $personnel->code = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }

            // Sync full_name from parent User if missing
            if (empty($personnel->full_name) && $personnel->user_id) {
                $user = \App\Models\User::find($personnel->user_id);
                if ($user) {
                    $personnel->full_name = $user->full_name;
                }
            }
        });

        static::updating(function (Personnel $personnel) {
            // Sync full_name from parent User if missing
            if (empty($personnel->full_name) && $personnel->user_id) {
                $user = \App\Models\User::find($personnel->user_id);
                if ($user) {
                    $personnel->full_name = $user->full_name;
                }
            }
        });
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
