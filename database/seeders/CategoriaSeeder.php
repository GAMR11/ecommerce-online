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
        Categoria::firstOrCreate(["nombre"=>"Moto",'descripcion'=>'Motos prácticas para un transporte rápido y eficiente en la ciudad.']);
        Categoria::firstOrCreate(["nombre"=>"Motocicleta",'descripcion'=>'Motocicletas potentes para una experiencia de conducción segura y cómoda.']);
        Categoria::firstOrCreate(["nombre"=>"Pasola",'descripcion'=>'Pasolas ideales para desplazamientos cortos y económicos.']);
        Categoria::firstOrCreate(["nombre"=>"Vitrina Frigorifica",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Lavadora Digital",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Lavadora Manual",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Secadora Digital",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Televisor",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Licuadora",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Tostadora",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Microondas",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Horno",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Cocina",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Ropero",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Congelador",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Espejo",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Minibar",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Nevera",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Ventilador",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Caja de sonido",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Bicicleta",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Patineta",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Monopatin",'descripcion'=>'']);
        Categoria::firstOrCreate(["nombre"=>"Celular",'descripcion'=>'']);
















    }
}
