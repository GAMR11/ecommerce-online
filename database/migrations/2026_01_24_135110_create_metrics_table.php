<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();
            $table->string('tool', 50)->index();
            $table->json('data');
            $table->timestamp('timestamp')->index();
            $table->timestamps();

            $table->index(['type', 'tool', 'timestamp']);
            $table->index(['tool', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
