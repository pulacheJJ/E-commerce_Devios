<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('elementos_carrito', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('carrito_id');
            $table->unsignedBigInteger('variante_id');
            $table->integer('cantidad');
            $table->timestamps();

            // FK Explícitas
            $table->foreign('carrito_id')
                  ->references('id')
                  ->on('carritos')
                  ->onDelete('cascade'); // Si se borra el carrito, limpian sus productos

            $table->foreign('variante_id')
                  ->references('id')
                  ->on('variantes_producto')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elementos_carrito');
    }
};
