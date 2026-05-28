<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('categoria_id');
            $table->string('nombre', 150);
            $table->string('slug', 180)->unique();
            $table->text('descripcion')->nullable();
            $table->string('marca', 100)->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->timestamps();
            $table->softDeletes(); // Borrado lógico para proteger el histórico

            // FK Explícita
            $table->foreign('categoria_id')
                  ->references('id')
                  ->on('categorias')
                  ->onDelete('restrict'); // Bloquea el borrado si tiene productos asociados
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};