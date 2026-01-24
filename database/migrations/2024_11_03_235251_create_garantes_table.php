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
        Schema::create('garantes', function (Blueprint $table) {
            $table->id(); // Identificador único
            // $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade'); // Relación con la tabla de clientes
            $table->string('nombre');
            $table->string('apellido');
            $table->string('identificacion')->unique(); // Identificación única del garante
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
        Schema::dropIfExists('garantes');
    }
};
