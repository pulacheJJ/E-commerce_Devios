<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('pedido_id');
            $table->enum('metodo_pago', ['tarjeta', 'yape', 'plin', 'transferencia']);
            $table->string('id_transaccion', 100)->nullable()->unique(); // Código del banco/pasarela
            $table->decimal('monto', 10, 2);
            $table->enum('estado_pago', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('respuesta_pasarela')->nullable(); // Almacena el log de error de la tarjeta
            $table->timestamps();

            // FK Explícita
            $table->foreign('pedido_id')
                  ->references('id')
                  ->on('pedidos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};