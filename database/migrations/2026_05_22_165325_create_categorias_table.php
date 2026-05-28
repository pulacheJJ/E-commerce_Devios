<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->unsignedBigInteger('parent_id')->nullable(); // Para la relación recursiva
            $table->string('nombre', 100);
            $table->string('slug', 120)->unique();
            $table->boolean('esta_activa')->default(true);
            $table->timestamps();

            // FK Explícita autorreferenciada
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categorias')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};