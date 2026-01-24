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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade'); // Relación con cliente
            $table->foreignId('kardex_cliente_id')->nullable()->constrained('kardex_clientes')->onDelete('set null'); // Relación opcional con kardex
            $table->string('tipo_pago'); // Tipo de pago (ej: Efectivo, Tarjeta, etc.)
            $table->string('comprobante')->nullable(); // Comprobante opcional si es necesario
            $table->decimal('monto_pagado', 10, 2); // Monto que abona el cliente
            $table->decimal('saldo_restante', 10, 2); // Saldo restante después del pago
            $table->date('fecha_pago'); // Fecha del pago
            $table->text('comentarios')->nullable(); // Comentarios adicionales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
