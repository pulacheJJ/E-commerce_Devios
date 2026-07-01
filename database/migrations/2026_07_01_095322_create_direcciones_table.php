<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('distrito_id', 6);
            $table->string('direccion_exacta', 255);
            $table->string('referencia', 255)->nullable();
            $table->boolean('is_predeterminada')->default(false); // Define si es su dirección por defecto
            
            $table->foreign('distrito_id')->references('id')->on('distritos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};