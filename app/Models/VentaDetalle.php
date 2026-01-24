<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentaDetalle extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'venta_id',
        // 'producto_id',
        'inventario_id',
        'cantidad',
        'precio_unitario',
    ];

    // Definir la relación con el modelo Venta
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    // public function producto()
    // {
    //     return $this->belongsTo(Producto::class);
    // }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
}
