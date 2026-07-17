<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Renombramos 'nombre' a 'nombres'
            $table->renameColumn('nombre', 'nombres');

            // Agregamos los nuevos campos
            // En instalaciones nuevas, el nombre vigente después del rename es `nombres`.
            $table->string('apellidos')->nullable()->after('nombres');
            $table->string('tipo_documento', 20)->default('DNI')->after('telefono');
            $table->string('numero_documento', 20)->nullable()->after('tipo_documento');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->renameColumn('nombres', 'nombre');
            $table->dropColumn(['apellidos', 'tipo_documento', 'numero_documento']);
        });
    }
};
