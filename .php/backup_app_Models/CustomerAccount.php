<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\BusinessPriority;

class CustomerAccount extends Model
{
    protected $table = 'customer_accounts';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code', 'name', 'phone', 'email', 'business_priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'business_priority' => BusinessPriority::class,
            'is_active'         => 'boolean',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'deleted_at'        => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'account_id');
    }
}
