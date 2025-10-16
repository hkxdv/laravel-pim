<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plantilla de migración para trabajar con columnas/tables JSONB en PostgreSQL.
     *
     * Este archivo sirve como ejemplo práctico de:
     * - Convertir columnas existentes de JSON a JSONB.
     * - Agregar nuevas columnas JSONB con valor por defecto.
     * - Crear tablas que usan JSONB y sus índices GIN.
     * - Crear índices GIN sobre columnas JSONB para acelerar consultas.
     *
     * Adáptalo a tus necesidades: cambia nombres de tablas/columnas e índices
     * según tu modelo de datos. Todas las operaciones se ejecutan solo en PostgreSQL.
     *
     * Reemplaza en los ejemplos:
     * - example_table_a / json_data
     * - example_table_b / form_data / extra_data
     * por los nombres reales de tus tablas y columnas.
     */
    public function up(): void
    {
        // Solo ejecutar en PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            // -----------------------------------------------------------------
            // Ejemplo 1: Convertir columna existente de JSON -> JSONB + índice GIN
            // Tabla: example_table_a | Columna: json_data
            // -----------------------------------------------------------------
            if (Schema::hasTable('example_table_a') && Schema::hasColumn('example_table_a', 'json_data')) {
                DB::statement('ALTER TABLE example_table_a
                    ALTER COLUMN json_data TYPE jsonb
                    USING json_data::jsonb');

                DB::statement('CREATE INDEX IF NOT EXISTS example_table_a_json_data_gin_idx
                    ON example_table_a USING gin (json_data)');
            }

            // -----------------------------------------------------------------
            // Ejemplo 2: Convertir otra columna existente y/o agregar nueva JSONB
            // Tabla: example_table_b | Columnas: form_data (convertir) y extra_data (agregar)
            // -----------------------------------------------------------------
            if (Schema::hasTable('example_table_b')) {
                // Convertir form_data a JSONB si existe
                if (Schema::hasColumn('example_table_b', 'form_data')) {
                    DB::statement('ALTER TABLE example_table_b
                        ALTER COLUMN form_data TYPE jsonb
                        USING form_data::jsonb');

                    DB::statement('CREATE INDEX IF NOT EXISTS example_table_b_form_data_gin_idx
                        ON example_table_b USING gin (form_data)');
                }

                // Agregar una nueva columna JSONB con valor por defecto {}
                DB::statement("ALTER TABLE example_table_b
                    ADD COLUMN IF NOT EXISTS extra_data jsonb NOT NULL DEFAULT '{}'::jsonb");

                DB::statement('CREATE INDEX IF NOT EXISTS example_table_b_extra_data_gin_idx
                    ON example_table_b USING gin (extra_data)');
            }

            // -----------------------------------------------------------------
            // Ejemplo 3: Crear una tabla que usa JSONB + índice GIN
            // Tabla: example_jsonb_entities | Columna: payload
            // -----------------------------------------------------------------
            if (! Schema::hasTable('example_jsonb_entities')) {
                DB::statement(<<<'SQL'
                    CREATE TABLE IF NOT EXISTS example_jsonb_entities (
                        id BIGSERIAL PRIMARY KEY,
                        payload jsonb NOT NULL DEFAULT '{}'::jsonb,
                        created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOW(),
                        updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOW()
                    )
                SQL);

                DB::statement('CREATE INDEX IF NOT EXISTS example_jsonb_entities_payload_gin_idx
                    ON example_jsonb_entities USING gin (payload)');
            }
        }
    }

    /**
     * Revierte los ejemplos aplicados arriba.
     *
     * Ten en cuenta que, si tu modelo original no usaba JSON
     * (por ejemplo, si agregaste columnas nuevas), aquí se elimina
     * lo agregado y se revierten tipos a JSON cuando aplica.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Revertir Ejemplo 1 (example_table_a.json_data)
            if (Schema::hasTable('example_table_a') && Schema::hasColumn('example_table_a', 'json_data')) {
                DB::statement('DROP INDEX IF EXISTS example_table_a_json_data_gin_idx');
                DB::statement('ALTER TABLE example_table_a
                    ALTER COLUMN json_data TYPE json
                    USING json_data::json');
            }

            // Revertir Ejemplo 2 (example_table_b.form_data + example_table_b.extra_data)
            if (Schema::hasTable('example_table_b')) {
                if (Schema::hasColumn('example_table_b', 'form_data')) {
                    DB::statement('DROP INDEX IF EXISTS example_table_b_form_data_gin_idx');
                    DB::statement('ALTER TABLE example_table_b
                        ALTER COLUMN form_data TYPE json
                        USING form_data::json');
                }

                DB::statement('DROP INDEX IF EXISTS example_table_b_extra_data_gin_idx');
                DB::statement('ALTER TABLE example_table_b DROP COLUMN IF EXISTS extra_data');
            }

            // Revertir Ejemplo 3 (tabla example_jsonb_entities)
            if (Schema::hasTable('example_jsonb_entities')) {
                DB::statement('DROP INDEX IF EXISTS example_jsonb_entities_payload_gin_idx');
                DB::statement('DROP TABLE IF EXISTS example_jsonb_entities');
            }
        }
    }
};
