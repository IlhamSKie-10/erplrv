<?php

namespace App\Models;

use App\Enums\ReturnResolution;
use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturn extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id',
        'reported_by_id',
        'reason',
        'photo_proof_path',
        'resolution',
        'status',
        'priority',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'resolution' => ReturnResolution::class,
            'status' => ReturnStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }
}
