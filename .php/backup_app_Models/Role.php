<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\RoleCode;

class Role extends Model
{
    protected $table = 'roles';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'description'];

    protected function casts(): array
    {
        return [
            'code' => RoleCode::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withPivot('assigned_at');
    }
}
