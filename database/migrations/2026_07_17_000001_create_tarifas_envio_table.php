<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_envio', function (Blueprint $table): void {
            $table->id();
            $table->string('provincia_id', 4)->nullable();
            $table->decimal('costo', 12, 2);
            $table->boolean('es_predeterminada')->default(false);
            $table->boolean('esta_activa')->default(true);
            $table->unsignedTinyInteger('predeterminada_activa_slot')->nullable()
                ->storedAs('IF(es_predeterminada = 1 AND esta_activa = 1, 1, NULL)');
            $table->timestamps();

            $table->unique('provincia_id');
            $table->unique('predeterminada_activa_slot');
            $table->foreign('provincia_id')->references('id')->on('provincias')->onDelete('restrict');
        });

        DB::statement('ALTER TABLE tarifas_envio ADD CONSTRAINT chk_tarifas_envio_costo CHECK (costo >= 0)');
        DB::statement("ALTER TABLE tarifas_envio ADD CONSTRAINT chk_tarifas_envio_tipo CHECK ((es_predeterminada = 1 AND provincia_id IS NULL) OR (es_predeterminada = 0 AND provincia_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas_envio');
    }
};
