<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\DesignTaskStatus;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DesignTask extends Model
{
    use HasUuids;

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
            'status'       => DesignTaskStatus::class,
            // print_sticker : plain string ('YES'|'NO'|'REQUIRED_LATER')
            //   — sengaja TIDAK di-cast ke enum agar Filament SelectColumn
            //     dapat langsung membandingkan string state vs. option keys.
            'cut_methods'  => 'array',      // JSON → PHP array
            'forwarded_at' => 'datetime',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
            // design_acc_at TIDAK di-cast datetime agar accessor bisa
            // mengembalikan format string yang dibutuhkan form.
        ];
    }

    // ── Accessor design_acc_at ────────────────────────────────────────────
    // Mengembalikan "Y-m-d H:i:s" sehingga DateTimePicker dapat membacanya.
    // Untuk mendapatkan Carbon: Carbon::parse($record->getRawOriginal('design_acc_at'))
    public function getDesignAccAtAttribute(mixed $value): ?string
    {
        return blank($value) ? null : \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function assignedDesigner(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'assigned_designer_id');
    }
}
