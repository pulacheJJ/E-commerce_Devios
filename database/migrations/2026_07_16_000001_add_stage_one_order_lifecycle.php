<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variantes_producto', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('carritos', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->index();
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('inventory_released_at')->nullable();
        });

        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente','pagado','enviado','cancelado','expirado') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        if (DB::table('pedidos')->where('estado', 'expirado')->exists()) {
            throw new \RuntimeException('No se puede revertir mientras existan pedidos expirados; sus datos no serán reinterpretados automáticamente.');
        }

        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente','pagado','enviado','cancelado') NOT NULL DEFAULT 'pendiente'");

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['expires_at', 'inventory_released_at']);
        });
        Schema::table('carritos', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
        Schema::table('variantes_producto', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
