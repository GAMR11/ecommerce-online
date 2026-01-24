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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id(); // Crea una columna 'id' auto-incremental como clave primaria
            $table->string('estado')->nullable(); // Crea la columna 'estado' como cadena de caracteres
            $table->foreignId('usuario_id')->constrained('users'); // Crea la columna 'usuario_id' como clave foránea que referencia a 'usuarios'
            $table->decimal('total', 10, 2); // Crea la columna 'total' con 10 dígitos en total y 2 decimales
            $table->text('comentario')->nullable();
            $table->softDeletes(); // Agrega la columna 'deleted_at'
            $table->timestamps(); // Crea las columnas 'created_at' y 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
