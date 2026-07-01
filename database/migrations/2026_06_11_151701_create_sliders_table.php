<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->nullable();
            $table->string('subtitulo')->nullable();
            $table->string('imagen_url'); 
            $table->string('url_enlace')->nullable(); // Nuevo: Para redirigir al dar clic al banner
            $table->integer('orden')->default(0); 
            $table->boolean('activo')->default(true); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};