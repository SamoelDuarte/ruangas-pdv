<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackerPing extends Model
{
    use HasFactory;

    protected $fillable = [
        'carro_id',
        'tracker_address_stay_id',
        'imei',
        'packet_type',
        'packet_origin',
        'protocol',
        'device_name',
        'raw_message',
        'latitude',
        'longitude',
        'altitude',
        'speed',
        'ignition',
        'in_motion',
        'address_line',
        'geocode_source',
        'gps_at',
        'received_at',
        'metadata',
    ];

    protected $casts = [
        'gps_at' => 'datetime',
        'received_at' => 'datetime',
        'metadata' => 'array',
        'ignition' => 'boolean',
        'in_motion' => 'boolean',
    ];

    public function carro()
    {
        return $this->belongsTo(Carro::class);
    }

    public function stay()
    {
        return $this->belongsTo(TrackerAddressStay::class, 'tracker_address_stay_id');
    }
}
