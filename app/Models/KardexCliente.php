<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KardexCliente extends Model
{
    use HasFactory;

    public $fillable = [
        'cliente_id',
        'venta_id',
        'garante_id',
        'fecha_compra',
        'monto_total',
        'entrada',
        'num_cuotas',
        'monto_cuota',
        'saldo_pendiente',
        'estado',
        'interes',
        'fecha_vencimiento',
        'saldo_pendiente_mora'
    ];

     // Relación con el modelo Cliente
     public function cliente()
     {
         return $this->belongsTo(Cliente::class, 'cliente_id');
     }
     public function garante()
     {
         return $this->hasOne(Garante::class, 'garante_id');
     }

     // Relación con el modelo Venta
     public function venta()
     {
         return $this->belongsTo(Venta::class, 'venta_id');
     }

      // (Opcional) Relación con el modelo HistorialPago
    public function historialPagos()
    {
        return $this->hasMany(HistorialPago::class, 'kardex_cliente_id');
    }
}
