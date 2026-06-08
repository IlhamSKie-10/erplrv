<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'provinces';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'name'];

    public function regencies()
    {
        return $this->hasMany(Regency::class, 'province_id', 'id');
    }
}
