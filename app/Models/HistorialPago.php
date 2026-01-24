<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialPago extends Model
{
    use HasFactory;
    public $fillable =[
        'cliente_id',
        'kardex_cliente_id',
        'monto_pagado',
        'fecha_pago',
        'metodo_pago',
        'comentarios',
        'comprobante',
        'saldo_restante',
        'estado_pago',
        'usuario_id'
    ];


     // Relación con el modelo KardexCliente
     public function kardexCliente()
     {
         return $this->belongsTo(KardexCliente::class, 'kardex_cliente_id');
     }

     // (Opcional) Relación con el modelo Cliente a través de KardexCliente
    // public function cliente()
    // {
    //     return $this->belongsTo(Cliente::class, 'cliente_id', 'id')
    //                 ->join('kardex_clientes', 'kardex_clientes.id', '=', 'historial_pagos.kardex_cliente_id');
    // }
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
     // Relación polimórfica para una sola imagen
     public function imagen()
     {
         return $this->morphMany(Imagen::class, 'imageable');
     }

     public function usuario()
     {
         return $this->belongsTo(User::class, 'usuario_id');
     }
}
