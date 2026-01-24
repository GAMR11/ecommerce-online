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
        Schema::create('kardex_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade'); // Relación con la tabla de clientes
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade'); // Relación con la venta asociada
            $table->foreignId('garante_id')->nullable()->constrained('clientes')->onDelete('cascade');
            $table->date('fecha_compra'); // Fecha de la compra
            $table->decimal('monto_total', 10, 2); // Monto total de la compra
            $table->decimal('entrada', 10, 2); // Monto de la entrada inicial
            $table->integer('num_cuotas'); // Número de cuotas
            $table->decimal('monto_cuota', 10, 2); // Monto de cada cuota
            $table->decimal('saldo_pendiente', 10, 2); // Saldo restante por pagar
            $table->string('estado')->default('pendiente');
            $table->date('fecha_vencimiento'); // Fecha de la compra
            $table->float('interes')->default(0); // Porcentaje de interés aplicado (si aplica)
            $table->float('saldo_pendiente_mora')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex_clientes');
    }
};
