<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carro extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nome',
        'placa',
        'modelo',
        'imei_rastreador',
    ];

    public function abastecimentos()
    {
        return $this->hasMany(Abastecimento::class);
    }

    public function trocaOleos()
    {
        return $this->hasMany(TrocaOleo::class);
    }

    public function trackerPings()
    {
        return $this->hasMany(TrackerPing::class);
    }

    public function trackerAddressStays()
    {
        return $this->hasMany(TrackerAddressStay::class);
    }

    public function trackerCommands()
    {
        return $this->hasMany(TrackerCommand::class);
    }
}
