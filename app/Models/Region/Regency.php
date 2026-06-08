<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    protected $table = 'regencies';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'province_id', 'name'];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    public function districts()
    {
        return $this->hasMany(District::class, 'regency_id', 'id');
    }
}
