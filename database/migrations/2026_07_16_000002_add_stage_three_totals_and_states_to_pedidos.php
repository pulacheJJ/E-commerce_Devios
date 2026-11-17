<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente','creado','reservado','pendiente_pago','pagado','pago_fallido','expirado','cancelado','enviado') NOT NULL DEFAULT 'creado'");

        Schema::table('pedidos', function (Blueprint $table): void {
            $table->char('checkout_idempotency_key', 64)->nullable()->after('session_token_hash')->unique();
            $table->decimal('subtotal', 12, 2)->default(0)->after('referencia');
            $table->decimal('descuento_total', 12, 2)->default(0)->after('subtotal');
            $table->decimal('costo_envio', 12, 2)->default(0)->after('descuento_total');
            $table->timestamp('stock_reserved_at')->nullable()->after('expires_at');
        });

        DB::statement('ALTER TABLE pedidos MODIFY total DECIMAL(12,2) NOT NULL');
        DB::table('pedidos')->update([
            'subtotal' => DB::raw('total'),
            'descuento_total' => 0,
            'costo_envio' => 0,
        ]);
        DB::table('pedidos')->where('estado', 'pendiente')->update([
            'estado' => 'pendiente_pago',
            'stock_reserved_at' => DB::raw('created_at'),
        ]);
        DB::table('pedidos')->whereIn('estado', ['pagado', 'enviado'])->whereNull('stock_reserved_at')->update([
            'stock_reserved_at' => DB::raw('created_at'),
        ]);

        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('creado','reservado','pendiente_pago','pagado','pago_fallido','expirado','cancelado','enviado') NOT NULL DEFAULT 'creado'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente','creado','reservado','pendiente_pago','pagado','pago_fallido','expirado','cancelado','enviado') NOT NULL DEFAULT 'pendiente'");

        DB::table('pedidos')->whereIn('estado', ['creado', 'reservado', 'pendiente_pago', 'pago_fallido'])->update([
            'estado' => 'pendiente',
        ]);

        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropUnique(['checkout_idempotency_key']);
            $table->dropColumn([
                'checkout_idempotency_key',
                'subtotal',
                'descuento_total',
                'costo_envio',
                'stock_reserved_at',
            ]);
        });

        DB::statement('ALTER TABLE pedidos MODIFY total DECIMAL(10,2) NOT NULL');
        DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente','pagado','enviado','cancelado','expirado') NOT NULL DEFAULT 'pendiente'");
    }
};
