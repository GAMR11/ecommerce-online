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
        Schema::create('historial_pagos', function (Blueprint $table) {
            $table->id(); // Identificador único
            $table->foreignId('kardex_cliente_id')->constrained('kardex_clientes')->onDelete('cascade'); // Relación con la tabla de kardex_clientes
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users'); // Crea la columna 'usuario_id' como clave foránea que referencia a 'usuarios'
            $table->decimal('monto_pagado', 10, 2); // Monto pagado en el pago
            $table->date('fecha_pago'); // Fecha del pago
            $table->string('metodo_pago')->nullable(); // Método de pago (efectivo, transferencia, etc.)
            $table->string('comentarios')->nullable(); // Comentarios opcionales sobre el pago
            $table->string('estado_pago')->nullable(); // Comentarios opcionales sobre el pago
            //saldo restante
            $table->decimal('saldo_restante',10,2);
            $table->string('comprobante')->nullable();
            $table->timestamps(); // Created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_pagos');
    }
};
