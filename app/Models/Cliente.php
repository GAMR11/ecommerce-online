<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory;
    // use SoftDeletes;

    public $fillable =[
        'nombre',
        'apellidos',
        'identificacion',
        'direccion',
        'telefono',
    ];

     // Relación con el modelo KardexCliente
     public function kardexClientes()
     {
         return $this->hasMany(KardexCliente::class, 'cliente_id');
     }

     // (Opcional) Relación con el modelo HistorialPago a través de KardexCliente
     // Relación con el modelo HistorialPago (un cliente tiene muchos pagos)
     public function historialPagos()
     {
         return $this->hasMany(HistorialPago::class, 'cliente_id');
     }

     // Relación con el modelo Garante
    // public function garantes()
    // {
    //     return $this->hasMany(Garante::class, 'cliente_id');
    // }
    // Relación con el modelo Garante
    // public function garante()
    // {
    //     return $this->hasOne(Garante::class, 'cliente_id');
    // }
}
