<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Definir los atributos que son asignables en masa
    protected $fillable = [
        'nombre',
        'marca',
        'modelo',
        'color',
        'imagen',
        'descripcion',
        'precio_original',
        'precio_contado',
        'precio_credito',
        'categoria_id',
    ];

    // Relación de muchos a uno con el modelo Categoria
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }

    public function imagenes(){
        return $this->morphMany(Imagen::class, 'imageable');
    }

}
