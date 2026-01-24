<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Venta extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'usuario_id',
        'total',
        'estado',
        'comentario'
    ];

    // Definir la relación con el modelo VentaDetalle
    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    // Relación con el modelo User/Usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
        // Si tu modelo de usuario es diferente, reemplaza User::class con Usuario::class
    }

    //  // (Opcional) Relación con el modelo KardexCliente
    //  public function kardexCliente()
    //  {
    //      return $this->belongsTo(KardexCliente::class, 'cliente_id');
    //  }
    // Relación con el modelo KardexCliente
    // public function kardexClientes()
    // {
    //     return $this->hasMany(KardexCliente::class, 'venta_id');
    // }

    public function kardexCliente()
    {
        return $this->hasOne(KardexCliente::class, 'venta_id');
    }



}
