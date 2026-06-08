<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    protected $table = 'product_types';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function models(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'product_type_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_type_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'product_type_id');
    }
}
