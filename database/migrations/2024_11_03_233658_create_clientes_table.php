<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id(); // Identificador único
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('identificacion')->unique(); // Identificación única del cliente
            $table->string('direccion');
            $table->string('telefono');
            $table->timestamps(); // Created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
