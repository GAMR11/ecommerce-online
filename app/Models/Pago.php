<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'kardex_cliente_id',
        'tipo_pago',
        'comprobante',
        'monto_pagado',
        'saldo_restante',
        'fecha_pago',
        'comentarios',
    ];

    // Relación con Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Relación con KardexCliente (si es necesario)
    public function kardexCliente()
    {
        return $this->belongsTo(KardexCliente::class);
    }
    
}
