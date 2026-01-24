<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garante extends Model
{
    use HasFactory;
    public $fillable =[
        'nombre',
        'apellido',
        'identificacion',
        'direccion',
        'telefono',
        // 'cliente_id'
    ];

    // Relación con el modelo Cliente
    // public function cliente()
    // {
    //     return $this->belongsTo(Cliente::class, 'cliente_id');
    // }
}
