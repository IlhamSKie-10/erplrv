<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'regency_id', 'name'];

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }
}
