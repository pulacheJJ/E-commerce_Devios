<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UbigeoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Leer los archivos
        $depsJson = json_decode(File::get(database_path('data/ubigeo_peru_2016_departamentos.json')), true);
        $provsJson = json_decode(File::get(database_path('data/ubigeo_peru_2016_provincias.json')), true);
        $distsJson = json_decode(File::get(database_path('data/ubigeo_peru_2016_distritos.json')), true);

        // 2. Limpiar tablas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('distritos')->truncate();
        DB::table('provincias')->truncate();
        DB::table('departamentos')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 3. Mapeo profesional
        // Nota: json_decode maneja los caracteres como \u00c1 automáticamente
        $departamentos = array_map(fn($d) => ['id' => $d['id'], 'nombre' => $d['name']], $depsJson);
        $provincias = array_map(fn($p) => ['id' => $p['id'], 'nombre' => $p['name'], 'departamento_id' => $p['department_id']], $provsJson);
        $distritos = array_map(fn($d) => ['id' => $d['id'], 'nombre' => $d['name'], 'provincia_id' => $d['province_id']], $distsJson);

        // 4. Insertar
        DB::table('departamentos')->insert($departamentos);
        DB::table('provincias')->insert($provincias);
        
        foreach (array_chunk($distritos, 500) as $chunk) {
            DB::table('distritos')->insert($chunk);
        }
    }
}