<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\AuditAction;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    // BigInt PK — not UUID
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id', 'entity_type', 'entity_id',
        'action', 'summary', 'before', 'after',
    ];

    protected function casts(): array
    {
        return [
            'action'     => AuditAction::class,
            'before'     => 'array',
            'after'      => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
