<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mora extends Model
{
    use HasFactory;

    public $fillable =
    [
        "fecha_generacion",
        "dias_mora",
        "saldo_pendiente",
        "interes_generado",
        "estado",
        "kardex_cliente_id"
    ];
}
