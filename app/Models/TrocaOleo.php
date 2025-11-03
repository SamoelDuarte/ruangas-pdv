<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrocaOleo extends Model
{
    use HasFactory;

    protected $table = 'troca_oleos';

    protected $fillable = [
        'carro_id',
        'data_troca',
        'observacoes',
    ];

    protected $casts = [
        'data_troca' => 'datetime',
    ];

    public function carro()
    {
        return $this->belongsTo(Carro::class);
    }
}
