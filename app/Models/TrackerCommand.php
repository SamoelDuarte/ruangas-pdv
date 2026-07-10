<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackerCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'carro_id',
        'imei',
        'command_name',
        'target_blocked',
        'command_payload',
        'status',
        'response_payload',
        'error_message',
        'requested_at',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'target_blocked' => 'boolean',
        'requested_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function carro()
    {
        return $this->belongsTo(Carro::class);
    }
}
