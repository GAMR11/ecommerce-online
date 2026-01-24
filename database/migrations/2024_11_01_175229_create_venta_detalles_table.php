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
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            // $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('inventario_id');

            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->softDeletes(); // Agrega la columna 'deleted_at'
            $table->timestamps();

            // Clave foránea
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
            $table->foreign('inventario_id')->references('id')->on('inventarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};
