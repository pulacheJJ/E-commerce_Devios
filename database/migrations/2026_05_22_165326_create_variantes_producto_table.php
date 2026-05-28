<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('variantes_producto', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('producto_id');
            $table->string('sku', 100)->unique();
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_oferta', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('color', 50)->nullable();
            $table->string('especificacion', 100)->nullable();
            $table->timestamps();

            // FK Explícita
            $table->foreign('producto_id')
                  ->references('id')
                  ->on('productos')
                  ->onDelete('cascade'); // Si vuela el producto base, vuelan sus variantes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variantes_producto');
    }
};
