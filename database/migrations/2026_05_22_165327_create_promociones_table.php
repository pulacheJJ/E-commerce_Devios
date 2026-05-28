<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->bigIncrements('id'); // PK Explícita
            $table->string('titulo', 150);
            $table->string('subtitulo', 255)->nullable();
            $table->string('ruta_banner', 255);
            $table->string('url_destino', 255)->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->boolean('esta_activa')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
