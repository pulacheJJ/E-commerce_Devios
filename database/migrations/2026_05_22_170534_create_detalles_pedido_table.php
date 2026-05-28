<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detalles_pedido', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedBigInteger('variante_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2); // Precio congelado al momento de comprar
            $table->timestamps();

            // FK Explícitas
            $table->foreign('pedido_id')
                  ->references('id')
                  ->on('pedidos')
                  ->onDelete('cascade'); // Si se elimina el pedido (auditoría), se borra su detalle

            $table->foreign('variante_id')
                  ->references('id')
                  ->on('variantes_producto')
                  ->onDelete('restrict'); // Bloquea borrar la variante si ya se vendió en un pedido
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_pedido');
    }
};
