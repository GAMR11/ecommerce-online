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
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();

            // Tipo de métrica: deployment, leadtime, deployment-result, incident
            $table->string('type', 50)->index();

            // Herramienta: github-actions, jenkins
            $table->string('tool', 50)->index();

            // Datos completos en JSON
            // Aquí guardamos toda la información específica de cada métrica
            $table->json('data');

            // Timestamp de cuando ocurrió el evento
            $table->timestamp('timestamp')->index();

            // Timestamps de Laravel (created_at, updated_at)
            $table->timestamps();

            // Índices compuestos para queries rápidas
            $table->index(['type', 'tool', 'timestamp']);
            $table->index(['tool', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
