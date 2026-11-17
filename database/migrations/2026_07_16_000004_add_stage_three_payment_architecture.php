<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pagos MODIFY metodo_pago VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE pagos MODIFY monto DECIMAL(12,2) NOT NULL');

        Schema::table('pagos', function (Blueprint $table): void {
            $table->string('proveedor', 50)->default('legacy')->after('pedido_id');
            $table->char('clave_idempotencia', 64)->nullable()->after('id_transaccion')->unique();
            $table->char('moneda', 3)->default('PEN')->after('monto');
            $table->timestamp('iniciado_at')->nullable()->after('estado_pago');
            $table->timestamp('confirmado_at')->nullable()->after('iniciado_at');
            $table->string('evento_confirmacion_id', 150)->nullable()->after('confirmado_at')->unique();
        });

        DB::table('pagos')->orderBy('id')->each(function (object $pago): void {
            DB::table('pagos')->where('id', $pago->id)->update([
                'clave_idempotencia' => hash('sha256', 'legacy-payment:'.$pago->id),
                'iniciado_at' => $pago->created_at,
                'confirmado_at' => $pago->estado_pago === 'aprobado' ? $pago->updated_at : null,
            ]);
        });

        Schema::create('eventos_webhook_pago', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pago_id')->nullable();
            $table->string('proveedor', 50);
            $table->string('evento_externo_id', 150);
            $table->char('payload_hash', 64);
            $table->string('estado_normalizado', 30);
            $table->timestamp('procesado_at')->nullable();
            $table->timestamps();

            $table->unique(['proveedor', 'evento_externo_id']);
            $table->foreign('pago_id')->references('id')->on('pagos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        if (DB::table('pagos')->whereNotIn('metodo_pago', ['tarjeta', 'yape', 'plin', 'transferencia'])->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan métodos de pago creados por proveedores de la Etapa 3.');
        }

        Schema::dropIfExists('eventos_webhook_pago');

        Schema::table('pagos', function (Blueprint $table): void {
            $table->dropUnique(['clave_idempotencia']);
            $table->dropUnique(['evento_confirmacion_id']);
            $table->dropColumn([
                'proveedor',
                'clave_idempotencia',
                'moneda',
                'iniciado_at',
                'confirmado_at',
                'evento_confirmacion_id',
            ]);
        });

        DB::statement('ALTER TABLE pagos MODIFY monto DECIMAL(10,2) NOT NULL');
        DB::statement("ALTER TABLE pagos MODIFY metodo_pago ENUM('tarjeta','yape','plin','transferencia') NOT NULL");
    }
};
