<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Categoria::create(["nombre"=>"Moto",'descripcion'=>'Motos prácticas para un transporte rápido y eficiente en la ciudad.']);
        Categoria::create(["nombre"=>"Motocicleta",'descripcion'=>'Motocicletas potentes para una experiencia de conducción segura y cómoda.']);
        Categoria::create(["nombre"=>"Pasola",'descripcion'=>'Pasolas ideales para desplazamientos cortos y económicos.']);
        Categoria::create(["nombre"=>"Vitrina Frigorifica",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Lavadora Digital",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Lavadora Manual",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Secadora Digital",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Televisor",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Licuadora",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Tostadora",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Microondas",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Horno",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Cocina",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Ropero",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Congelador",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Espejo",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Minibar",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Nevera",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Ventilador",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Caja de sonido",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Bicicleta",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Patineta",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Monopatin",'descripcion'=>'']);
        Categoria::create(["nombre"=>"Celular",'descripcion'=>'']);
















    }
}
