<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carro extends Model
{
    use HasFactory;
    
    protected $fillable = ['nome'];

    public function abastecimentos()
    {
        return $this->hasMany(Abastecimento::class);
    }

    public function trocaOleos()
    {
        return $this->hasMany(TrocaOleo::class);
    }
}
