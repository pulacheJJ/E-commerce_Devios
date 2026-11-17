<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_pedido', function (Blueprint $table): void {
            $table->string('producto_nombre')->nullable()->after('variante_id');
            $table->string('sku', 100)->nullable()->after('producto_nombre');
            $table->string('variante_descripcion')->nullable()->after('sku');
            $table->decimal('precio_base_unitario', 12, 2)->default(0)->after('variante_descripcion');
            $table->decimal('descuento_unitario', 12, 2)->default(0)->after('precio_base_unitario');
            $table->decimal('subtotal_linea', 12, 2)->default(0)->after('precio_unitario');
        });

        DB::statement('ALTER TABLE detalles_pedido MODIFY precio_unitario DECIMAL(12,2) NOT NULL');
        DB::statement(<<<'SQL'
            UPDATE detalles_pedido AS d
            INNER JOIN variantes_producto AS v ON v.id = d.variante_id
            INNER JOIN productos AS p ON p.id = v.producto_id
            SET d.producto_nombre = p.nombre,
                d.sku = v.sku,
                d.variante_descripcion = NULLIF(CONCAT_WS(' / ', NULLIF(v.color, ''), NULLIF(v.especificacion, '')), ''),
                d.precio_base_unitario = d.precio_unitario,
                d.descuento_unitario = 0,
                d.subtotal_linea = ROUND(d.precio_unitario * d.cantidad, 2)
        SQL);
    }

    public function down(): void
    {
        Schema::table('detalles_pedido', function (Blueprint $table): void {
            $table->dropColumn([
                'producto_nombre',
                'sku',
                'variante_descripcion',
                'precio_base_unitario',
                'descuento_unitario',
                'subtotal_linea',
            ]);
        });

        DB::statement('ALTER TABLE detalles_pedido MODIFY precio_unitario DECIMAL(10,2) NOT NULL');
    }
};
