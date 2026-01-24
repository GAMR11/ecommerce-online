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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('marca');
            $table->string('modelo');
            $table->string('color');
            $table->string('imagen')->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('precio_original', 10, 2);
            $table->decimal('precio_contado', 10, 2);
            $table->decimal('precio_credito', 10, 2);
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->softDeletes(); // Agrega la columna 'deleted_at'
            // Definir la relación con la tabla categorias
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
