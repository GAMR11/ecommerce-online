<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    use HasFactory;
    public $fillable=[
        "public_id","url"
    ];

    public function imageable(){
        return $this->morphTo();
    }

}
