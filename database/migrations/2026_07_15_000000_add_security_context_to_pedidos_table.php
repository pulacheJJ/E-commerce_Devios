<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('carrito_id')->nullable()->after('cliente_id');
            $table->char('carrito_token_hash', 64)->nullable()->after('carrito_id');
            $table->char('session_token_hash', 64)->nullable()->after('carrito_token_hash');

            $table->unique('carrito_id');
            $table->index('session_token_hash');
            $table->foreign('carrito_id')
                ->references('id')
                ->on('carritos')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['carrito_id']);
            $table->dropUnique(['carrito_id']);
            $table->dropIndex(['session_token_hash']);
            $table->dropColumn(['carrito_id', 'carrito_token_hash', 'session_token_hash']);
        });
    }
};
