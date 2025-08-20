<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abastecimento extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'carro_id',
        'litros_abastecido',
        'preco_por_litro',
        'km_atual',
        'data_abastecimento'
    ];

    protected $casts = [
        'data_abastecimento' => 'date',
        'litros_abastecido' => 'decimal:2',
        'preco_por_litro' => 'decimal:2',
        'km_atual' => 'decimal:2'
    ];

    public function carro()
    {
        return $this->belongsTo(Carro::class);
    }

    // Calcula o total pago
    public function getTotalPagoAttribute()
    {
        return $this->litros_abastecido * $this->preco_por_litro;
    }
}
