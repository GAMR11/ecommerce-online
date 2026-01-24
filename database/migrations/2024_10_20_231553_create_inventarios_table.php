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
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id'); // Relación con la tabla de productos
            $table->string('numero_serie')->nullable(); // Para productos con número de serie único
            $table->integer('cantidad')->default(1); // Cantidad en stock
            $table->timestamps();

            // Definir la relación con la tabla productos
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
