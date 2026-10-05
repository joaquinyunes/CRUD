<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexa cada clave foránea que no tenga índice.
 *
 * MySQL crea el índice solo al declarar la clave; PostgreSQL y SQLite no. En la
 * base que se despliega (PostgreSQL) cada JOIN de un reporte —ventas con sus
 * detalles, detalles con su producto— y cada "las ventas de este cliente"
 * recorría la tabla entera. Con unas decenas de miles de comprobantes se nota.
 *
 * Se recorre el esquema en vez de listar las tablas a mano. Si una clave ya está
 * cubierta por un índice (porque es la primera columna de uno compuesto, o
 * porque MySQL lo creó), no se duplica.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Schema::getTableListing(schemaQualified: false) as $tabla) {
            $indexados = collect(Schema::getIndexes($tabla))
                ->map(fn ($indice) => $indice['columns'][0] ?? null)
                ->filter()
                ->all();

            foreach (Schema::getForeignKeys($tabla) as $clave) {
                $columnas = $clave['columns'];

                if (in_array($columnas[0], $indexados, true)) {
                    continue;
                }

                Schema::table($tabla, fn (Blueprint $t) => $t->index($columnas));
                $indexados[] = $columnas[0];
            }
        }

        // Los listados y reportes filtran y ordenan por fecha sin mirar el estado.
        foreach (['ventas', 'compras'] as $tabla) {
            if (Schema::hasColumn($tabla, 'fecha') && ! $this->tieneIndiceEn($tabla, 'fecha')) {
                Schema::table($tabla, fn (Blueprint $t) => $t->index('fecha'));
            }
        }
    }

    public function down(): void
    {
        // Índices de rendimiento: dejarlos no rompe nada y quitarlos a ciegas, sin
        // saber cuáles puso MySQL por su cuenta, sí podría.
    }

    private function tieneIndiceEn(string $tabla, string $columna): bool
    {
        return collect(Schema::getIndexes($tabla))->contains(fn ($i) => ($i['columns'][0] ?? null) === $columna);
    }
};
