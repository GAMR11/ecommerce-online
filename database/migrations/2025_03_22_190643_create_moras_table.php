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
        Schema::create('moras', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_generacion')->useCurrent();
            $table->integer('dias_mora');
            $table->decimal('saldo_pendiente');
            $table->decimal('interes_generado');
            $table->string('estado')->nullable();
            $table->foreignId('kardex_cliente_id')->constrained('kardex_clientes')->onDelete('cascade'); // Relación con la tabla de kardex_clientes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moras');
    }
};
