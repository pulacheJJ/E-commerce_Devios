<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('cliente_id');
            $table->string('numero_pedido', 50)->unique();
            $table->text('direccion_envio'); // Dirección congelada históricamente
            $table->decimal('total', 10, 2);
            $table->enum('estado', ['pendiente', 'pagado', 'enviado', 'cancelado'])->default('pendiente');
            $table->timestamps();

            // FK Explícita
            $table->foreign('cliente_id')
                  ->references('id')
                  ->on('clientes')
                  ->onDelete('restrict'); // No permite borrar al cliente si tiene pedidos históricos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};