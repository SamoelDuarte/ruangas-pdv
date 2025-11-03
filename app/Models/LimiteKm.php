<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LimiteKm extends Model
{
    use HasFactory;

    protected $table = 'limite_kms';

    protected $fillable = [
        'km_limite',
    ];

    protected $casts = [
        'km_limite' => 'integer',
    ];
}
