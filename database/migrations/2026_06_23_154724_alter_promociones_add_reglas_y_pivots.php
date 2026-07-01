<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Agregamos la lógica matemática y la bandera del carrusel
        Schema::table('promociones', function (Blueprint $table) {
            $table->enum('tipo_descuento', ['porcentaje', 'fijo'])
                  ->default('porcentaje')
                  ->after('url_destino')
                  ->comment('Define si descontamos un % o un monto fijo en Soles');
            
            $table->decimal('valor_descuento', 10, 2)
                  ->default(0)
                  ->after('tipo_descuento');

            $table->boolean('destacada_en_carrusel')
                  ->default(false)
                  ->after('esta_activa')
                  ->comment('Si es true, los productos de esta promo salen en el home');
        });

        // 2. Tabla Pivot para enlazar Promociones con Categorías enteras
        Schema::create('categoria_promocion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocion_id')->constrained('promociones')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
        });

        // 3. Tabla Pivot para enlazar Promociones con Productos específicos
        Schema::create('producto_promocion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocion_id')->constrained('promociones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_promocion');
        Schema::dropIfExists('categoria_promocion');
        
        Schema::table('promociones', function (Blueprint $table) {
            $table->dropColumn(['tipo_descuento', 'valor_descuento', 'destacada_en_carrusel']);
        });
    }
};