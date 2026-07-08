<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackerAddressStay extends Model
{
    use HasFactory;

    protected $fillable = [
        'carro_id',
        'imei',
        'address_line',
        'latitude',
        'longitude',
        'arrived_at',
        'left_at',
        'permanence_seconds',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function carro()
    {
        return $this->belongsTo(Carro::class);
    }

    public function pings()
    {
        return $this->hasMany(TrackerPing::class);
    }
}
