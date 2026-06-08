<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use App\Enums\NotificationLevel;

class Notification extends Model
{
    protected $table = 'app_notifications';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['title', 'body', 'order_id', 'level'];

    protected function casts(): array
    {
        return [
            'level'      => NotificationLevel::class,
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }
}
