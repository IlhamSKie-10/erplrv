<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ReminderStatus;

class Reminder extends Model
{
    protected $table = 'reminders';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id', 'assignee_id', 'title', 'message',
        'status', 'due_at', 'remind_at',
    ];

    protected function casts(): array
    {
        return [
            'status'     => ReminderStatus::class,
            'due_at'     => 'datetime',
            'remind_at'  => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
