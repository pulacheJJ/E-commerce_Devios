<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imagenes_producto', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('producto_id');
            $table->string('ruta_imagen', 255);
            $table->boolean('es_principal')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // FK Explícita
            $table->foreign('producto_id')
                  ->references('id')
                  ->on('productos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes_producto');
    }
};