<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->repairClientesColumns();

        DB::transaction(function (): void {
            $this->consolidateCartDuplicates();
            $this->deduplicatePivot('categoria_promocion', 'promocion_id', 'categoria_id');
            $this->deduplicatePivot('producto_promocion', 'promocion_id', 'producto_id');
            $this->normalizeDefaultAddresses();
        });

        if (! $this->hasIndex('elementos_carrito', 'elementos_carrito_carrito_variante_unique')) {
            Schema::table('elementos_carrito', function (Blueprint $table) {
                $table->unique(['carrito_id', 'variante_id'], 'elementos_carrito_carrito_variante_unique');
            });
        }

        if (! $this->hasIndex('categoria_promocion', 'categoria_promocion_pair_unique')) {
            Schema::table('categoria_promocion', function (Blueprint $table) {
                $table->unique(['promocion_id', 'categoria_id'], 'categoria_promocion_pair_unique');
            });
        }

        if (! $this->hasIndex('producto_promocion', 'producto_promocion_pair_unique')) {
            Schema::table('producto_promocion', function (Blueprint $table) {
                $table->unique(['promocion_id', 'producto_id'], 'producto_promocion_pair_unique');
            });
        }

        // NULL permite muchas direcciones normales; el cliente aparece una sola vez cuando es predeterminada.
        Schema::table('direcciones', function (Blueprint $table) {
            $table->unsignedTinyInteger('predeterminada_slot')
                ->nullable()
                ->storedAs('IF(is_predeterminada = 1, 1, NULL)');
            $table->unique(['cliente_id', 'predeterminada_slot'], 'direcciones_una_predeterminada_por_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            $table->dropUnique('direcciones_una_predeterminada_por_cliente');
            $table->dropColumn('predeterminada_slot');
        });
        Schema::table('producto_promocion', fn (Blueprint $table) => $table->dropUnique('producto_promocion_pair_unique'));
        Schema::table('categoria_promocion', fn (Blueprint $table) => $table->dropUnique('categoria_promocion_pair_unique'));
        Schema::table('elementos_carrito', fn (Blueprint $table) => $table->dropUnique('elementos_carrito_carrito_variante_unique'));
    }

    private function repairClientesColumns(): void
    {
        if (Schema::hasColumn('clientes', 'nombre') && ! Schema::hasColumn('clientes', 'nombres')) {
            Schema::table('clientes', fn (Blueprint $table) => $table->renameColumn('nombre', 'nombres'));
        }

        Schema::table('clientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('clientes', 'apellidos')) {
                $table->string('apellidos')->nullable()->after('nombres');
            }
            if (! Schema::hasColumn('clientes', 'tipo_documento')) {
                $table->string('tipo_documento', 20)->default('DNI')->after('telefono');
            }
            if (! Schema::hasColumn('clientes', 'numero_documento')) {
                $table->string('numero_documento', 20)->nullable()->after('tipo_documento');
            }
        });
    }

    private function consolidateCartDuplicates(): void
    {
        $groups = DB::table('elementos_carrito')
            ->select('carrito_id', 'variante_id', DB::raw('SUM(cantidad) AS cantidad_total'), DB::raw('MIN(id) AS conservar_id'))
            ->groupBy('carrito_id', 'variante_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::table('elementos_carrito')->where('id', $group->conservar_id)->update(['cantidad' => $group->cantidad_total]);
            DB::table('elementos_carrito')
                ->where('carrito_id', $group->carrito_id)
                ->where('variante_id', $group->variante_id)
                ->where('id', '<>', $group->conservar_id)
                ->delete();
        }
    }

    private function deduplicatePivot(string $table, string $first, string $second): void
    {
        $duplicates = DB::table($table)
            ->select($first, $second, DB::raw('MIN(id) AS conservar_id'))
            ->groupBy($first, $second)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table($table)
                ->where($first, $duplicate->{$first})
                ->where($second, $duplicate->{$second})
                ->where('id', '<>', $duplicate->conservar_id)
                ->delete();
        }
    }

    private function normalizeDefaultAddresses(): void
    {
        $clients = DB::table('direcciones')
            ->where('is_predeterminada', true)
            ->groupBy('cliente_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cliente_id');

        foreach ($clients as $clienteId) {
            $keepId = DB::table('direcciones')
                ->where('cliente_id', $clienteId)
                ->where('is_predeterminada', true)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('direcciones')
                ->where('cliente_id', $clienteId)
                ->where('is_predeterminada', true)
                ->where('id', '<>', $keepId)
                ->update(['is_predeterminada' => false]);
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
